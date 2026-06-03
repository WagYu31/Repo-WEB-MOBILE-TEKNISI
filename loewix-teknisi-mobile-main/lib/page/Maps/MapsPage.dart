import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
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

  // Untuk tracking animation controllers
  final List<AnimationController> _animationControllers = [];

  @override
  void initState() {
    super.initState();
    _initializeMap();
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

      if (taskLat != null && taskLon != null) {
        _taskLocation = LatLng(taskLat, taskLon);

        if (!mounted) return;
        setState(() {
          _markers.add(
            Marker(
              point: _taskLocation!,
              width: 40,
              height: 40,
              child: const Icon(
                Icons.location_on,
                color: Colors.red,
                size: 40,
              ),
            ),
          );

          if (taskRadius != null) {
            _circles.add(
              CircleMarker(
                point: _taskLocation!,
                radius: taskRadius,
                useRadiusInMeter: true,
                color: Colors.blue.withValues(alpha: 0.15),
                borderColor: Colors.blue,
                borderStrokeWidth: 2,
              ),
            );
          }
        });
      }
    }
  }

  /// Update user marker on the map (reusable for both cached and fresh positions)
  void _updateUserMarker() {
    if (_userLocation == null || !mounted) return;
    setState(() {
      // Remove existing user marker
      _markers.removeWhere((marker) => marker.point != _taskLocation);
      // Re-add task marker if exists
      if (_taskLocation != null) {
        _markers.add(
          Marker(
            point: _taskLocation!,
            width: 40,
            height: 40,
            child: const Icon(Icons.location_on, color: Colors.red, size: 40),
          ),
        );
      }
      // Add user location marker
      _markers.add(
        Marker(
          point: _userLocation!,
          width: 40,
          height: 40,
          child: Container(
            decoration: BoxDecoration(
              color: Colors.blue,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
              boxShadow: [
                BoxShadow(
                  color: Colors.blue.withValues(alpha: 0.3),
                  blurRadius: 8,
                  spreadRadius: 2,
                ),
              ],
            ),
            child: const Icon(Icons.person, color: Colors.white, size: 18),
          ),
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
                  ? 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/{z}/{x}/{y}?access_token=${AppConstants.mapboxAccessToken}'
                  : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              userAgentPackageName: 'com.loewix.teknisi',
              maxZoom: 19,
            ),
            CircleLayer(circles: _circles),
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
          top: 16,
          right: 16,
          child: Column(
            children: [
              _buildFabButton(
                heroTag: 'fab_gps_location',
                icon: Icons.my_location,
                tooltip: 'Lokasi Saya',
                color: Colors.blue,
                onPressed: () {
                  if (_userLocation != null) {
                    _animatedMove(_userLocation!, 17.0);
                  } else {
                    _getUserLocation();
                  }
                },
              ),
              const SizedBox(height: 8),
              if (_taskLocation != null)
                _buildFabButton(
                  heroTag: 'fab_task_location',
                  icon: Icons.flag,
                  tooltip: 'Lokasi Tugas',
                  color: Colors.red,
                  onPressed: _centerToTaskLocation,
                ),
              if (_userLocation != null && _taskLocation != null) ...[
                const SizedBox(height: 8),
                _buildFabButton(
                  heroTag: 'fab_fit_bounds',
                  icon: Icons.zoom_out_map,
                  tooltip: 'Lihat Semua',
                  color: Colors.green,
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
    return FloatingActionButton(
      heroTag: heroTag,
      onPressed: onPressed,
      tooltip: tooltip,
      mini: true,
      backgroundColor: Colors.white,
      foregroundColor: color,
      elevation: 4,
      child: Icon(icon),
    );
  }

  void _centerToTaskLocation() {
    if (_taskLocation == null) return;
    _animatedMove(_taskLocation!, 17.0);
  }

  /// Fit bounds untuk menampilkan kedua lokasi (user dan task)
  void _fitBothLocations() {
    if (_userLocation == null || _taskLocation == null || !_mapReady) return;

    final bounds = LatLngBounds.fromPoints([_userLocation!, _taskLocation!]);

    // Hitung center dan zoom yang tepat
    final centerLat =
        (bounds.northEast.latitude + bounds.southWest.latitude) / 2;
    final centerLng =
        (bounds.northEast.longitude + bounds.southWest.longitude) / 2;
    final center = LatLng(centerLat, centerLng);

    // Hitung zoom berdasarkan jarak
    final distance = const Distance().as(
      LengthUnit.Meter,
      _userLocation!,
      _taskLocation!,
    );

    double zoom;
    if (distance < 500) {
      zoom = 16.0;
    } else if (distance < 1000) {
      zoom = 15.0;
    } else if (distance < 5000) {
      zoom = 13.0;
    } else if (distance < 10000) {
      zoom = 12.0;
    } else {
      zoom = 10.0;
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
          return;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        setState(() {
          _isLoading = false;
          _currentAddress = "Izin lokasi ditolak permanen";
        });
        return;
      }

      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.best,
      ).timeout(
        const Duration(seconds: 12),
        onTimeout: () => Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.medium,
        ),
      );

      if (!mounted) return;

      GoogleMapSample.currentPosition = position;
      _userLocation = LatLng(position.latitude, position.longitude);

      _updateUserMarker();
      setState(() { _isLoading = false; });

      if (_mapReady && mounted) {
        _animatedMove(_userLocation!, 16.0);
      }

      _getAddressFromLatLng(position);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _currentAddress = "Gagal mendapatkan lokasi";
      });
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
    // Dispose semua animation controllers yang masih aktif
    for (final controller in _animationControllers) {
      controller.dispose();
    }
    _animationControllers.clear();
    super.dispose();
  }
}
