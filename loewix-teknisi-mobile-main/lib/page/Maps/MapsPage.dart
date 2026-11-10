import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../constants/app_constants.dart';
import '../../service/provider/Maps/MapsProvider.dart';
import '../../service/model/task/TaskAllResponse.dart';

class GoogleMapSample extends StatefulWidget {
  static Position? currentPosition;
  final DataTask? taskData;

  const GoogleMapSample({super.key, this.taskData});

  @override
  State<GoogleMapSample> createState() => GoogleMapSampleState();
}

class GoogleMapSampleState extends State<GoogleMapSample>
    with TickerProviderStateMixin {
  final MapController _mapController = MapController();
  final List<Marker> _markers = [];
  final List<CircleMarker> _circles = [];
  String _currentAddress = "Mencari alamat...";
  bool _isLoading = true;
  bool _mapReady = false;

  // Untuk animasi
  LatLng? _userLocation;
  LatLng? _taskLocation;

  // Real road route coordinates from OSRM
  List<LatLng> _roadRoutePoints = [];
  double? _roadDistanceMeters;
  int? _roadDurationSeconds;
  LatLng? _lastRoutedUserLocation;

  // Untuk tracking animation controllers
  final List<AnimationController> _animationControllers = [];
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat();
    _initializeMap();
  }

  String _getFormattedDistance() {
    if (_userLocation == null || _taskLocation == null) return '';

    // If real road routing data is available, use it!
    if (_roadDistanceMeters != null) {
      final km = (_roadDistanceMeters! / 1000).toStringAsFixed(1);
      final mins = _roadDurationSeconds != null
          ? (_roadDurationSeconds! / 60).round().clamp(1, 240)
          : ((_roadDistanceMeters! / 1000 / 30) * 60).round().clamp(1, 240);
      return '$km km • ~$mins mnt';
    }

    // Fallback: straight line distance
    final meters = const Distance().as(LengthUnit.Meter, _userLocation!, _taskLocation!);
    if (meters < 1000) {
      return '${meters.round()} m • ~1 mnt';
    }
    final km = (meters / 1000).toStringAsFixed(1);
    final minutes = (meters / 1000 / 30 * 60).round().clamp(1, 180);
    return '$km km • ~$minutes mnt';
  }

  double _getTechnicianHeading() {
    // If device heading is available and valid from Geolocator
    final heading = GoogleMapSample.currentPosition?.heading;
    if (heading != null && heading > 0) {
      return heading * (pi / 180.0);
    }
    // Calculate angle towards task destination
    if (_userLocation != null && _taskLocation != null) {
      final lat1 = _userLocation!.latitude * (pi / 180.0);
      final lon1 = _userLocation!.longitude * (pi / 180.0);
      final lat2 = _taskLocation!.latitude * (pi / 180.0);
      final lon2 = _taskLocation!.longitude * (pi / 180.0);

      final dLon = lon2 - lon1;
      final y = sin(dLon) * cos(lat2);
      final x = cos(lat1) * sin(lat2) - sin(lat1) * cos(lat2) * cos(dLon);

      final bearing = atan2(y, x);
      return bearing;
    }
    return 0.0;
  }

  Widget _buildTechnicianMarkerWidget() {
    final bearing = _getTechnicianHeading();
    return SizedBox(
      width: 80,
      height: 90,
      child: Stack(
        alignment: Alignment.center,
        clipBehavior: Clip.none,
        children: [
          // Animated Radar Pulse
          AnimatedBuilder(
            animation: _pulseController,
            builder: (context, child) {
              final val = _pulseController.value;
              return Container(
                width: 36 + (val * 38),
                height: 36 + (val * 38),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: const Color(0xFFFF5B22).withValues(alpha: (1 - val) * 0.28),
                  border: Border.all(
                    color: const Color(0xFFFF5B22).withValues(alpha: (1 - val) * 0.55),
                    width: 1.5,
                  ),
                ),
              );
            },
          ),
          // Soft ground shadow circle
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.black.withValues(alpha: 0.12),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.15),
                  blurRadius: 10,
                  spreadRadius: 2,
                ),
              ],
            ),
          ),
          // Lego Toy Motorcycle 2D Sprite (Rotated along route)
          Transform.rotate(
            angle: bearing,
            child: Image.asset(
              'assets/lego_motorcycle.png',
              width: 36,
              height: 78,
              fit: BoxFit.contain,
              filterQuality: FilterQuality.high,
            ),
          ),
          // Floating "Anda" Badge (always remains upright)
          Positioned(
            top: 0,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(6),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.28),
                    blurRadius: 4,
                    offset: const Offset(0, 1),
                  ),
                ],
              ),
              child: const Text(
                'Anda',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDestinationMarkerWidget() {
    final customerName = widget.taskData?.dataCustomer.nama ?? 'Lokasi Tugas';
    final distText = _getFormattedDistance();

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Floating Callout Pill
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.15),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Text(
                customerName,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 10.5,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF0F172A),
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              if (distText.isNotEmpty)
                Text(
                  distText,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 9,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFFFF5B22),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 2),
        // Teardrop Pin
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: const LinearGradient(
              colors: [Color(0xFFEF4444), Color(0xFFB91C1C)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            border: Border.all(color: Colors.white, width: 2.5),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.25),
                blurRadius: 8,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: const Center(
            child: Icon(
              Icons.business_rounded,
              color: Colors.white,
              size: 18,
            ),
          ),
        ),
      ],
    );
  }

  List<Polyline> _buildPolylines() {
    if (_userLocation == null || _taskLocation == null) return [];
    final points = _roadRoutePoints.isNotEmpty
        ? _roadRoutePoints
        : [_userLocation!, _taskLocation!];

    return [
      // Glow Layer
      Polyline(
        points: points,
        strokeWidth: 8.0,
        color: const Color(0xFFFF5B22).withValues(alpha: 0.25),
        strokeCap: StrokeCap.round,
        strokeJoin: StrokeJoin.round,
      ),
      // Core Road Route Line
      Polyline(
        points: points,
        strokeWidth: 4.5,
        color: const Color(0xFFFF5B22),
        borderColor: Colors.white,
        borderStrokeWidth: 1.5,
        strokeCap: StrokeCap.round,
        strokeJoin: StrokeJoin.round,
      ),
    ];
  }

  /// Fetch real turn-by-turn road route via OSRM (Open Source Routing Machine)
  Future<void> _fetchRoadRoute({bool force = false}) async {
    if (_userLocation == null || _taskLocation == null) return;

    if (!force && _lastRoutedUserLocation != null) {
      final distMoved = const Distance().as(
        LengthUnit.Meter,
        _lastRoutedUserLocation!,
        _userLocation!,
      );
      if (distMoved < 30 && _roadRoutePoints.isNotEmpty) {
        return;
      }
    }

    _lastRoutedUserLocation = _userLocation;

    try {
      final url = Uri.parse(
        'https://router.project-osrm.org/route/v1/driving/'
        '${_userLocation!.longitude},${_userLocation!.latitude};'
        '${_taskLocation!.longitude},${_taskLocation!.latitude}'
        '?overview=full&geometries=geojson',
      );

      final response = await http.get(url).timeout(const Duration(seconds: 7));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['code'] == 'Ok' && (data['routes'] as List).isNotEmpty) {
          final route = data['routes'][0];
          final coords = route['geometry']['coordinates'] as List;
          final points = coords.map<LatLng>((c) {
            final lon = (c[0] as num).toDouble();
            final lat = (c[1] as num).toDouble();
            return LatLng(lat, lon);
          }).toList();

          final dist = (route['distance'] as num?)?.toDouble();
          final dur = (route['duration'] as num?)?.round();

          if (mounted && points.isNotEmpty) {
            setState(() {
              _roadRoutePoints = points;
              _roadDistanceMeters = dist;
              _roadDurationSeconds = dur;
            });
            debugPrint('🛣️ OSRM Road Route loaded: ${points.length} road coordinates, $dist m');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('⚠️ Error fetching OSRM road route: $e');
    }

    // Fallback if offline/failed
    if (mounted && _roadRoutePoints.isEmpty) {
      setState(() {
        _roadRoutePoints = [_userLocation!, _taskLocation!];
      });
    }
  }

  Future<void> _initializeMap() async {
    _addTaskLocationMarker();
    // Try to show last known position instantly while waiting for GPS
    await _loadLastKnownPosition();
    await _getUserLocation();
  }

  /// Load cached/last known position for instant map display
  Future<void> _loadLastKnownPosition() async {
    try {
      final lastPosition = await Geolocator.getLastKnownPosition();
      if (lastPosition != null && mounted) {
        GoogleMapSample.currentPosition = lastPosition;
        _userLocation = LatLng(lastPosition.latitude, lastPosition.longitude);
        _updateUserMarker();
        _fetchRoadRoute();
        if (_mapReady) {
          _animatedMove(_userLocation!, 16.0);
        }
      }
    } catch (_) {
      // Silently ignore — we'll get fresh position next
    }
  }

  void _addTaskLocationMarker() {
    if (widget.taskData?.lat != null && widget.taskData?.lon != null) {
      final taskLat = double.tryParse(widget.taskData!.lat.toString());
      final taskLon = double.tryParse(widget.taskData!.lon.toString());
      final taskRadius = widget.taskData?.rad != null
          ? double.tryParse(widget.taskData!.rad.toString())
          : null;

      if (taskLat != null && taskLon != null && taskLat != 0.0 && taskLon != 0.0) {
        _applyTaskLocation(LatLng(taskLat, taskLon), taskRadius);
        return;
      }
    }

    // Fallback: Jika koordinat lat/lon dari admin di web masih kosong (NULL),
    // coba cari koordinat otomatis dari teks alamat customer
    _fallbackGeocodeCustomerAddress();
  }

  void _applyTaskLocation(LatLng location, double? radius) {
    _taskLocation = location;

    if (!mounted) return;
    setState(() {
      if (radius != null && radius > 0) {
        _circles.clear();
        _circles.add(
          CircleMarker(
            point: _taskLocation!,
            radius: radius,
            useRadiusInMeter: true,
            color: const Color(0xFFFF5B22).withValues(alpha: 0.10),
            borderColor: const Color(0xFFFF5B22).withValues(alpha: 0.5),
            borderStrokeWidth: 1.5,
          ),
        );
      }
      _updateUserMarker();
    });

    if (_userLocation != null) {
      _fetchRoadRoute(force: true);
      if (_mapReady) {
        Future.delayed(const Duration(milliseconds: 300), () {
          if (mounted) _fitBothLocations();
        });
      }
    }
  }

  Future<void> _fallbackGeocodeCustomerAddress() async {
    final rawAddr = widget.taskData?.dataCustomer.alamat?.toString().trim();
    if (rawAddr == null || rawAddr.isEmpty) return;

    final kota = widget.taskData?.dataCustomer.kota?.toString().trim();

    // Buat daftar query bertingkat dari spesifik ke umum
    final List<String> queries = [];
    queries.add(rawAddr);

    if (rawAddr.contains(',')) {
      final parts = rawAddr.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty).toList();
      if (parts.length >= 2) {
        // Contoh: "Ruko Food City no.92, Green Lake City, Jakarta Barat" -> "Green Lake City, Jakarta Barat"
        queries.add(parts.sublist(1).join(', '));
      }
      if (parts.length >= 3) {
        queries.add('${parts[1]}, ${parts[2]}');
      }
    }

    if (kota != null && kota.isNotEmpty && !rawAddr.toLowerCase().contains(kota.toLowerCase())) {
      queries.add('$rawAddr, $kota');
    }

    // 1. Coba geocoding native Android
    for (final q in queries) {
      try {
        final locations = await locationFromAddress(q).timeout(const Duration(seconds: 3));
        if (locations.isNotEmpty) {
          final loc = locations.first;
          if (loc.latitude != 0.0 && loc.longitude != 0.0) {
            _applyTaskLocation(LatLng(loc.latitude, loc.longitude), 100);
            return;
          }
        }
      } catch (_) {}
    }

    // 2. Fallback Nominatim OSM Geocoding API
    for (final q in queries) {
      try {
        final url = Uri.parse(
          'https://nominatim.openstreetmap.org/search?format=json&q='
          '${Uri.encodeComponent(q)}&limit=1',
        );
        final res = await http.get(url, headers: {
          'User-Agent': 'LoewixTeknisiApp/1.0',
        }).timeout(const Duration(seconds: 3));

        if (res.statusCode == 200) {
          final data = json.decode(res.body);
          if (data is List && data.isNotEmpty) {
            final lat = double.tryParse(data[0]['lat']?.toString() ?? '');
            final lon = double.tryParse(data[0]['lon']?.toString() ?? '');
            if (lat != null && lon != null && lat != 0.0 && lon != 0.0) {
              _applyTaskLocation(LatLng(lat, lon), 100);
              return;
            }
          }
        }
      } catch (_) {}
    }
  }

  /// Update user marker on the map (reusable for both cached and fresh positions)
  void _updateUserMarker() {
    if (_userLocation == null || !mounted) return;
    setState(() {
      _markers.clear();
      // Re-add task marker if exists
      if (_taskLocation != null) {
        _markers.add(
          Marker(
            point: _taskLocation!,
            width: 160,
            height: 96,
            alignment: Alignment.bottomCenter,
            child: _buildDestinationMarkerWidget(),
          ),
        );
      }
      // Add user location marker with Gojek / Shopee style radar pulse
      _markers.add(
        Marker(
          point: _userLocation!,
          width: 80,
          height: 90,
          alignment: Alignment.center,
          child: _buildTechnicianMarkerWidget(),
        ),
      );
    });
  }

  /// Animasi perpindahan map dari posisi saat ini ke target
  void _animatedMove(LatLng destLocation, double destZoom) {
    if (!_mapReady || !mounted) return;

    final camera = _mapController.camera;
    final latTween = Tween<double>(
      begin: camera.center.latitude,
      end: destLocation.latitude,
    );
    final lngTween = Tween<double>(
      begin: camera.center.longitude,
      end: destLocation.longitude,
    );
    final zoomTween = Tween<double>(
      begin: camera.zoom,
      end: destZoom,
    );

    final controller = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );

    // Track controller for cleanup
    _animationControllers.add(controller);

    final Animation<double> animation = CurvedAnimation(
      parent: controller,
      curve: Curves.easeInOutCubic,
    );

    controller.addListener(() {
      if (mounted) {
        _mapController.move(
          LatLng(latTween.evaluate(animation), lngTween.evaluate(animation)),
          zoomTween.evaluate(animation),
        );
      }
    });

    controller.addStatusListener((status) {
      if (status == AnimationStatus.completed ||
          status == AnimationStatus.dismissed) {
        _animationControllers.remove(controller);
        controller.dispose();
      }
    });

    controller.forward();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        FlutterMap(
          mapController: _mapController,
          options: MapOptions(
            // Center on task location if available, otherwise default
            initialCenter: _taskLocation ?? LatLng(
              AppConstants.defaultLatitude,
              AppConstants.defaultLongitude,
            ),
            initialZoom: _taskLocation != null ? 16.0 : AppConstants.defaultZoom,
            onMapReady: () {
              if (!mounted) return;
              setState(() {
                _mapReady = true;
              });
            },
          ),
          children: [
            TileLayer(
              urlTemplate: AppConstants.useMapbox
                  ? 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/256/{z}/{x}/{y}?access_token=${AppConstants.mapboxAccessToken}'
                  : 'https://{s}.google.com/vt/lyrs=m&hl=id&x={x}&y={y}&z={z}',
              subdomains: AppConstants.useMapbox
                  ? const ['a', 'b', 'c', 'd']
                  : const ['mt0', 'mt1', 'mt2', 'mt3'],
              maxZoom: 20,
            ),
            CircleLayer(circles: _circles),
            PolylineLayer(polylines: _buildPolylines()),
            MarkerLayer(markers: _markers),
          ],
        ),
        if (_isLoading)
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.9),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.1),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox(
                    width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.blue),
                  ),
                  SizedBox(width: 12),
                  Text(
                    'Mencari lokasi...',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      fontWeight: FontWeight.w500,
                      color: Color(0xFF374151),
                    ),
                  ),
                ],
              ),
            ),
          ),
        Positioned(
          bottom: 275,
          right: 16,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _buildFabButton(
                heroTag: 'fab_gps_location',
                icon: Icons.my_location_rounded,
                tooltip: 'Lokasi Saya',
                color: const Color(0xFF0284C7),
                onPressed: () {
                  if (_userLocation != null) {
                    _animatedMove(_userLocation!, 17.0);
                  } else {
                    _getUserLocation();
                  }
                },
              ),
              const SizedBox(height: 10),
              if (_taskLocation != null)
                _buildFabButton(
                  heroTag: 'fab_task_location',
                  icon: Icons.location_on_rounded,
                  tooltip: 'Lokasi Tugas',
                  color: const Color(0xFFEF4444),
                  onPressed: _centerToTaskLocation,
                ),
              if (_userLocation != null && _taskLocation != null) ...[
                const SizedBox(height: 10),
                _buildFabButton(
                  heroTag: 'fab_fit_bounds',
                  icon: Icons.alt_route_rounded,
                  tooltip: 'Lihat Rute',
                  color: const Color(0xFFFF5B22),
                  onPressed: _fitBothLocations,
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildFabButton({
    required String heroTag,
    required IconData icon,
    required String tooltip,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onPressed,
          customBorder: const CircleBorder(),
          child: Center(
            child: Icon(icon, color: color, size: 20),
          ),
        ),
      ),
    );
  }

  void _centerToTaskLocation() {
    if (_taskLocation == null) return;
    _animatedMove(_taskLocation!, 17.0);
  }

  /// Fit bounds untuk menampilkan kedua lokasi (user dan task)
  void _fitBothLocations() {
    if (_userLocation == null || _taskLocation == null || !_mapReady) return;

    final pointsToFit = _roadRoutePoints.isNotEmpty
        ? _roadRoutePoints
        : [_userLocation!, _taskLocation!];
    final bounds = LatLngBounds.fromPoints(pointsToFit);

    // Hitung center dan geser sedikit ke atas (north) agar pas di area pandang atas bottom panel
    final centerLat = (bounds.northEast.latitude + bounds.southWest.latitude) / 2;
    final centerLng = (bounds.northEast.longitude + bounds.southWest.longitude) / 2;
    final center = LatLng(centerLat - 0.0035, centerLng);

    final distance = const Distance().as(
      LengthUnit.Meter,
      _userLocation!,
      _taskLocation!,
    );

    double zoom;
    if (distance < 500) {
      zoom = 15.5;
    } else if (distance < 1000) {
      zoom = 14.5;
    } else if (distance < 3000) {
      zoom = 13.5;
    } else if (distance < 8000) {
      zoom = 12.0;
    } else {
      zoom = 10.5;
    }

    _animatedMove(center, zoom);
  }

  Future<void> _getUserLocation() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
    });

    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!mounted) return;
      if (!serviceEnabled) {
        setState(() {
          _isLoading = false;
          _currentAddress = "Layanan lokasi tidak aktif";
        });
        Provider.of<MapsProvider>(context, listen: false)
            .setError("Layanan lokasi tidak aktif");
        return;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (!mounted) return;
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (!mounted) return;
        if (permission == LocationPermission.denied) {
          setState(() {
            _isLoading = false;
            _currentAddress = "Izin lokasi ditolak";
          });
          Provider.of<MapsProvider>(context, listen: false)
              .setError("Izin lokasi ditolak");
          return;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        setState(() {
          _isLoading = false;
          _currentAddress = "Izin lokasi ditolak permanen";
        });
        Provider.of<MapsProvider>(context, listen: false)
            .setError("Izin lokasi ditolak permanen");
        return;
      }

      // Strategy: try multiple accuracy levels with fallback
      Position? position;
      
      // 1. Try last known position first (instant)
      try {
        position = await Geolocator.getLastKnownPosition();
        debugPrint('📍 Last known position: ${position?.latitude}, ${position?.longitude}');
      } catch (_) {}

      // 2. Try best accuracy (15 seconds timeout)
      if (position == null) {
        try {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.best,
          ).timeout(const Duration(seconds: 15));
          debugPrint('📍 Best accuracy position obtained');
        } catch (e) {
          debugPrint('⚠️ Best accuracy failed: $e');
        }
      } else {
        // We have last known, but still try to get fresh position in parallel
        // Use the last known for now, upgrade later if fresh comes
        try {
          final freshPosition = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
          ).timeout(const Duration(seconds: 10));
          position = freshPosition;
          debugPrint('📍 Fresh high accuracy position obtained');
        } catch (_) {
          debugPrint('📍 Using last known position (fresh fetch timed out)');
        }
      }

      // 3. Fallback: high accuracy
      if (position == null) {
        try {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
          ).timeout(const Duration(seconds: 10));
          debugPrint('📍 High accuracy position obtained');
        } catch (e) {
          debugPrint('⚠️ High accuracy failed: $e');
        }
      }

      // 4. Fallback: medium accuracy
      if (position == null) {
        try {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.medium,
          ).timeout(const Duration(seconds: 8));
          debugPrint('📍 Medium accuracy position obtained');
        } catch (e) {
          debugPrint('⚠️ Medium accuracy failed: $e');
        }
      }

      // 5. Last resort: low accuracy
      if (position == null) {
        try {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.low,
          ).timeout(const Duration(seconds: 5));
          debugPrint('📍 Low accuracy position obtained');
        } catch (e) {
          debugPrint('⚠️ Low accuracy also failed: $e');
        }
      }

      if (position == null) {
        throw Exception('Semua metode lokasi gagal');
      }

      if (!mounted) return;

      GoogleMapSample.currentPosition = position;
      _userLocation = LatLng(position.latitude, position.longitude);

      _updateUserMarker();
      _fetchRoadRoute();
      setState(() { _isLoading = false; });

      // Set MapsProvider ready immediately after getting position
      Provider.of<MapsProvider>(context, listen: false)
          .setData("Lokasi ditemukan", false);

      if (_mapReady && mounted) {
        _animatedMove(_userLocation!, 16.0);
      }

      _getAddressFromLatLng(position);
    } catch (e) {
      debugPrint('❌ Location error: $e');
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _currentAddress = "Gagal mendapatkan lokasi";
      });
      Provider.of<MapsProvider>(context, listen: false)
          .setError("Gagal mendapatkan lokasi");
    }
  }

  Future<void> _getAddressFromLatLng(Position position) async {
    try {
      List<Placemark> placemarks = await placemarkFromCoordinates(
        position.latitude,
        position.longitude,
      );

      if (!mounted) return;

      Placemark place = placemarks[0];

      setState(() {
        _currentAddress =
            "${place.street}, ${place.locality}, ${place.postalCode}, ${place.country}";
        Provider.of<MapsProvider>(context, listen: false)
            .setData(_currentAddress, false);
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _currentAddress = "Tidak dapat menemukan alamat";
      });
    }
  }

  @override
  void dispose() {
    _pulseController.dispose();
    // Dispose semua animation controllers yang masih aktif
    for (final controller in _animationControllers) {
      controller.dispose();
    }
    _animationControllers.clear();
    super.dispose();
  }
}
