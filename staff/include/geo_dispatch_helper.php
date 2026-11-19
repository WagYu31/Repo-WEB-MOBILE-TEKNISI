<?php
/**
 * Shared Geo & Zone Helper for Smart Dispatch (Auto-Susun Searah + Proximity Hints).
 * Provides:
 *  - Jabodetabek sub-area & macro-zone resolution from address + coordinates
 *  - Coordinate fallback (kegiatan lat/lon -> cust_coordinate -> area centroid)
 *  - Haversine + urban road factor distance & travel duration estimation
 */

if (!function_exists('dispatchGetAreaCatalog')) {
    function dispatchGetAreaCatalog(): array {
        // Ordered most-specific sub-areas first, macro-cities last.
        return [
            // JAKARTA UTARA & PANTAI UTARA
            ['kw' => ['pik 2', 'pik2', 'aloha pasir putih', 'kosambi barat', 'salembaran', 'teluknaga'], 'zone' => 'TANGERANG', 'sub' => 'PIK 2', 'lat' => -6.0580, 'lon' => 106.7020],
            ['kw' => ['pantai indah kapuk', 'akasia golf', 'kamal muara', 'kapuk muara', 'batavia pik', 'golf island', 'pik'], 'zone' => 'JAKUT', 'sub' => 'PIK', 'lat' => -6.1095, 'lon' => 106.7405],
            ['kw' => ['muara karang', 'pluit', 'penjaringan', 'teluk gong', 'pejagalan', 'waduk pluit'], 'zone' => 'JAKUT', 'sub' => 'Pluit', 'lat' => -6.1255, 'lon' => 106.7885],
            ['kw' => ['ancol', 'pademangan', 'mangga dua'], 'zone' => 'JAKUT', 'sub' => 'Pademangan', 'lat' => -6.1335, 'lon' => 106.8315],
            ['kw' => ['sunter', 'papanggo', 'danau sunter'], 'zone' => 'JAKUT', 'sub' => 'Sunter', 'lat' => -6.1430, 'lon' => 106.8665],
            ['kw' => ['kelapa gading', 'pegangsaan dua', 'moi', 'boulevard raya'], 'zone' => 'JAKUT', 'sub' => 'Klp. Gading', 'lat' => -6.1575, 'lon' => 106.9055],
            ['kw' => ['tanjung priok', 'warakas', 'kebon bawang', 'sungai bambu'], 'zone' => 'JAKUT', 'sub' => 'Tj. Priok', 'lat' => -6.1185, 'lon' => 106.8830],
            ['kw' => ['koja', 'rawa badak', 'tugu utara', 'tugu selatan'], 'zone' => 'JAKUT', 'sub' => 'Koja', 'lat' => -6.1180, 'lon' => 106.9120],
            ['kw' => ['cilincing', 'marunda', 'semper', 'rorotan', 'sukapura', 'kbn'], 'zone' => 'JAKUT', 'sub' => 'Cilincing', 'lat' => -6.1155, 'lon' => 106.9450],

            // JAKARTA BARAT
            ['kw' => ['puri indah', 'kembangan', 'meruya', 'joglo', 'srengseng', 'lippo puri'], 'zone' => 'JAKBAR', 'sub' => 'Puri/Kembangan', 'lat' => -6.1885, 'lon' => 106.7385],
            ['kw' => ['kebon jeruk', 'kedoya', 'duri kepa', 'kelapa dua', 'panjang'], 'zone' => 'JAKBAR', 'sub' => 'Kebon Jeruk', 'lat' => -6.1915, 'lon' => 106.7685],
            ['kw' => ['cengkareng', 'rawa buaya', 'duri kosambi', 'kapuk', 'taman palem', 'daan mogot'], 'zone' => 'JAKBAR', 'sub' => 'Cengkareng', 'lat' => -6.1485, 'lon' => 106.7315],
            ['kw' => ['kalideres', 'pegadungan', 'citra garden', 'tegal alur', 'semanan', 'kamal'], 'zone' => 'JAKBAR', 'sub' => 'Kalideres', 'lat' => -6.1445, 'lon' => 106.7035],
            ['kw' => ['grogol', 'petamburan', 'tanjung duren', 'tomang', 'jelambar', 'wijaya kusuma'], 'zone' => 'JAKBAR', 'sub' => 'Grogol', 'lat' => -6.1670, 'lon' => 106.7885],
            ['kw' => ['palmerah', 'slipi', 'kota bambu', 'kemanggisan'], 'zone' => 'JAKBAR', 'sub' => 'Slipi/Palmerah', 'lat' => -6.1950, 'lon' => 106.7975],
            ['kw' => ['taman sari', 'glodok', 'mangga besar', 'pinangsia', 'hayam wuruk', 'gajah mada'], 'zone' => 'JAKBAR', 'sub' => 'Glodok/Kota', 'lat' => -6.1475, 'lon' => 106.8170],
            ['kw' => ['tambora', 'jembatan lima', 'angke', 'pekojan', 'roa malaka'], 'zone' => 'JAKBAR', 'sub' => 'Tambora', 'lat' => -6.1440, 'lon' => 106.8035],

            // JAKARTA PUSAT
            ['kw' => ['gambir', 'cideng', 'petojo', 'kebon kelapa', 'duri pulo', 'medan merdeka'], 'zone' => 'JAKPUS', 'sub' => 'Gambir', 'lat' => -6.1705, 'lon' => 106.8185],
            ['kw' => ['tanah abang', 'bendungan hilir', 'benhil', 'karet tengsin', 'kebon melati', 'kebon kacang', 'thamrin'], 'zone' => 'JAKPUS', 'sub' => 'Tanah Abang', 'lat' => -6.1975, 'lon' => 106.8145],
            ['kw' => ['menteng', 'cikini', 'gondangdia', 'pegangsaan', 'kebon sirih', 'imam bonjol'], 'zone' => 'JAKPUS', 'sub' => 'Menteng', 'lat' => -6.1950, 'lon' => 106.8345],
            ['kw' => ['kemayoran', 'gunung sahari', 'serdang', 'sumur batu', 'cempaka baru'], 'zone' => 'JAKPUS', 'sub' => 'Kemayoran', 'lat' => -6.1585, 'lon' => 106.8520],
            ['kw' => ['sawah besar', 'pasar baru', 'kartini', 'karang anyar'], 'zone' => 'JAKPUS', 'sub' => 'Sawah Besar', 'lat' => -6.1555, 'lon' => 106.8325],
            ['kw' => ['senen', 'kramat', 'kwitang', 'salemba', 'kenari'], 'zone' => 'JAKPUS', 'sub' => 'Senen', 'lat' => -6.1835, 'lon' => 106.8465],
            ['kw' => ['cempaka putih', 'rawasari', 'johar baru'], 'zone' => 'JAKPUS', 'sub' => 'Cempaka Putih', 'lat' => -6.1765, 'lon' => 106.8700],

            // JAKARTA SELATAN
            ['kw' => ['setiabudi', 'kuningan', 'karet semanggi', 'semanggi', 'sudirman', 'gatot subroto', 'guntur', 'mega kuningan'], 'zone' => 'JAKSEL', 'sub' => 'Setiabudi/Kuningan', 'lat' => -6.2185, 'lon' => 106.8265],
            ['kw' => ['kebayoran baru', 'senopati', 'scbd', 'blok m', 'melawai', 'gandaria', 'cipete', 'radio dalam', 'wijaya'], 'zone' => 'JAKSEL', 'sub' => 'Kebayoran Baru', 'lat' => -6.2435, 'lon' => 106.7995],
            ['kw' => ['kebayoran lama', 'pondok indah', 'pondok pinang', 'permata hijau', 'tanah kusir', 'grogol utara', 'grogol selatan'], 'zone' => 'JAKSEL', 'sub' => 'Pd. Indah/Kby.Lama', 'lat' => -6.2520, 'lon' => 106.7775],
            ['kw' => ['pesanggrahan', 'petukangan', 'ulujami', 'bintaro pesanggrahan'], 'zone' => 'JAKSEL', 'sub' => 'Pesanggrahan', 'lat' => -6.2485, 'lon' => 106.7555],
            ['kw' => ['mampang', 'kemang', 'bangka', 'tegal parang', 'kuningan barat'], 'zone' => 'JAKSEL', 'sub' => 'Kemang/Mampang', 'lat' => -6.2545, 'lon' => 106.8195],
            ['kw' => ['tebet', 'casablanca', 'kasablanka', 'manggarai', 'bukit duri', 'kebon baru'], 'zone' => 'JAKSEL', 'sub' => 'Tebet', 'lat' => -6.2285, 'lon' => 106.8515],
            ['kw' => ['pancoran', 'kalibata', 'duren tiga', 'cikoko', 'pengadegan'], 'zone' => 'JAKSEL', 'sub' => 'Pancoran', 'lat' => -6.2515, 'lon' => 106.8445],
            ['kw' => ['cilandak', 'fatmawati', 'lebak bulus', 'pondok labu', 'tb simatupang', 'simatupang'], 'zone' => 'JAKSEL', 'sub' => 'Cilandak/Fatmawati', 'lat' => -6.2905, 'lon' => 106.7955],
            ['kw' => ['pasar minggu', 'pejaten', 'ragunan', 'jatipadang', 'cililandak timur', 'kebagusan', 'tanjung barat'], 'zone' => 'JAKSEL', 'sub' => 'Pasar Minggu', 'lat' => -6.2885, 'lon' => 106.8355],
            ['kw' => ['jagakarsa', 'lenteng agung', 'ciganjur', 'srengseng sawah'], 'zone' => 'JAKSEL', 'sub' => 'Jagakarsa', 'lat' => -6.3325, 'lon' => 106.8225],

            // JAKARTA TIMUR
            ['kw' => ['cakung', 'jakarta garden city', 'jgc', 'aeon jgc', 'pulo gebang', 'rawa terate', 'ujung menteng', 'penggilingan', 'krt radjiman', 'radjiman'], 'zone' => 'JAKTIM', 'sub' => 'Cakung', 'lat' => -6.1865, 'lon' => 106.9445],
            ['kw' => ['pulo gadung', 'pulogadung', 'rawamangun', 'kayu putih', 'jati', 'pisangan timur', 'cipinang'], 'zone' => 'JAKTIM', 'sub' => 'Pulo Gadung', 'lat' => -6.1925, 'lon' => 106.8965],
            ['kw' => ['matraman', 'utan kayu', 'pisangan baru', 'pal meriam'], 'zone' => 'JAKTIM', 'sub' => 'Matraman', 'lat' => -6.2025, 'lon' => 106.8665],
            ['kw' => ['jatinegara', 'kp melayu', 'kampung melayu', 'bidara cina', 'cipinang besar', 'cipinang muara'], 'zone' => 'JAKTIM', 'sub' => 'Jatinegara', 'lat' => -6.2255, 'lon' => 106.8755],
            ['kw' => ['duren sawit', 'pondok bambu', 'pondok kelapa', 'klender', 'malaka', 'radin inten', 'kalimalang'], 'zone' => 'JAKTIM', 'sub' => 'Duren Sawit', 'lat' => -6.2345, 'lon' => 106.9195],
            ['kw' => ['kramat jati', 'cawang', 'cililitan', 'condet', 'batu ampar', 'dukuh'], 'zone' => 'JAKTIM', 'sub' => 'Cawang/Kramat Jati', 'lat' => -6.2665, 'lon' => 106.8665],
            ['kw' => ['makasar', 'halim', 'cipinang melayu', 'pinang ranti'], 'zone' => 'JAKTIM', 'sub' => 'Halim/Makasar', 'lat' => -6.2695, 'lon' => 106.8925],
            ['kw' => ['pasar rebo', 'cijantung', 'kalisari', 'pekayon pasar rebo', 'gedong'], 'zone' => 'JAKTIM', 'sub' => 'Pasar Rebo', 'lat' => -6.3185, 'lon' => 106.8615],
            ['kw' => ['ciracas', 'cipayung', 'cibubur', 'kelapa dua wetan', 'lubang buaya', 'bambu apus', 'munjul'], 'zone' => 'JAKTIM', 'sub' => 'Cibubur/Ciracas', 'lat' => -6.3425, 'lon' => 106.8925],

            // TANGERANG & TANGSEL
            ['kw' => ['bsd', 'serpong', 'gading serpong', 'pagedangan', 'cisauk'], 'zone' => 'TANGSEL', 'sub' => 'BSD/Serpong', 'lat' => -6.2880, 'lon' => 106.6520],
            ['kw' => ['alam sutera', 'pakulonan', 'serpong utara'], 'zone' => 'TANGSEL', 'sub' => 'Alam Sutera', 'lat' => -6.2400, 'lon' => 106.6550],
            ['kw' => ['bintaro', 'pondok aren', 'jurang mangu', 'graha raya', 'parigi'], 'zone' => 'TANGSEL', 'sub' => 'Bintaro', 'lat' => -6.2735, 'lon' => 106.7125],
            ['kw' => ['ciputat', 'pamulang', 'pondok cabe', 'pisangan ciputat', 'rempoa'], 'zone' => 'TANGSEL', 'sub' => 'Ciputat/Pamulang', 'lat' => -6.3220, 'lon' => 106.7420],
            ['kw' => ['ciledug', 'karang tengah', 'larangan', 'kreio', 'kreo'], 'zone' => 'TANGERANG', 'sub' => 'Ciledug', 'lat' => -6.2265, 'lon' => 106.7120],
            ['kw' => ['cipondoh', 'green lake', 'poris', 'pinang', ' Gondrong'], 'zone' => 'TANGERANG', 'sub' => 'Cipondoh/GreenLake', 'lat' => -6.1855, 'lon' => 106.6885],
            ['kw' => ['dadap', 'kosambi', 'benda', 'soekarno hatta', 'juru mudi'], 'zone' => 'TANGERANG', 'sub' => 'Dadap/Bandara', 'lat' => -6.1085, 'lon' => 106.6820],
            ['kw' => ['karawaci', 'lippo karawaci', 'kelapa dua tangerang', 'curug', 'legok', 'bencongan'], 'zone' => 'TANGERANG', 'sub' => 'Karawaci', 'lat' => -6.2225, 'lon' => 106.6050],
            ['kw' => ['jatiuwung', 'cikupa', 'pasar kemis', 'balaraja', 'bitung', 'tigaraksa', 'citra raya'], 'zone' => 'TANGERANG', 'sub' => 'Cikupa/Jatiuwung', 'lat' => -6.2080, 'lon' => 106.5420],

            // BEKASI & CIKARANG
            ['kw' => ['harapan indah', 'medan satria', 'pejuang', 'tarumajaya'], 'zone' => 'BEKASI', 'sub' => 'Harapan Indah', 'lat' => -6.1815, 'lon' => 106.9765],
            ['kw' => ['summarecon bekasi', 'bekasi utara', 'bekasi barat', 'kranji', 'bintara', 'jakasampurna'], 'zone' => 'BEKASI', 'sub' => 'Bekasi Barat', 'lat' => -6.2285, 'lon' => 106.9755],
            ['kw' => ['pondok gede', 'jatiwaringin', 'jatibening', 'jatimakmur'], 'zone' => 'BEKASI', 'sub' => 'Pondok Gede', 'lat' => -6.2725, 'lon' => 106.9215],
            ['kw' => ['jatiasih', 'galaxy', 'pekayon', 'bekasi selatan', 'jatisampurna', 'kranggan'], 'zone' => 'BEKASI', 'sub' => 'Jatiasih/Galaxy', 'lat' => -6.2850, 'lon' => 106.9685],
            ['kw' => ['bekasi timur', 'rawumbu', 'rawalumbu', 'mustika jaya', 'bantar gebang', 'narogong'], 'zone' => 'BEKASI', 'sub' => 'Bekasi Timur', 'lat' => -6.2750, 'lon' => 107.0085],
            ['kw' => ['tambun', 'cibitung', 'grand wisata', 'mm2100'], 'zone' => 'BEKASI', 'sub' => 'Tambun/Cibitung', 'lat' => -6.2650, 'lon' => 107.0750],
            ['kw' => ['cikarang', 'jababeka', 'lippo cikarang', 'delta silicon', 'ejip', 'giic'], 'zone' => 'BEKASI', 'sub' => 'Cikarang', 'lat' => -6.3050, 'lon' => 107.1550],

            // DEPOK & BOGOR
            ['kw' => ['cinere', 'limo', 'gandul', 'meruyung'], 'zone' => 'DEPOK', 'sub' => 'Cinere', 'lat' => -6.3365, 'lon' => 106.7825],
            ['kw' => ['cimanggis', 'tapos', 'cisalak', 'kelapa dua depok', 'juanda depok'], 'zone' => 'DEPOK', 'sub' => 'Cimanggis', 'lat' => -6.3720, 'lon' => 106.8650],
            ['kw' => ['margonda', 'beji', 'pancoran mas', 'sukmajaya', 'sawangan', 'bojongsari'], 'zone' => 'DEPOK', 'sub' => 'Depok', 'lat' => -6.3950, 'lon' => 106.8225],
            ['kw' => ['gunung putri', 'cileungsi', 'kota wisata', 'narogong bogor', 'klapanunggal'], 'zone' => 'BOGOR', 'sub' => 'Gn. Putri/Cileungsi', 'lat' => -6.4150, 'lon' => 106.9550],
            ['kw' => ['cibinong', 'sentul', 'citeureup', 'bojong gede', 'parung'], 'zone' => 'BOGOR', 'sub' => 'Cibinong/Sentul', 'lat' => -6.4850, 'lon' => 106.8550],

            // MACRO-CITY FALLBACKS
            ['kw' => ['jakarta utara', 'jakut'], 'zone' => 'JAKUT', 'sub' => '', 'lat' => -6.1380, 'lon' => 106.8640],
            ['kw' => ['jakarta barat', 'jakbar'], 'zone' => 'JAKBAR', 'sub' => '', 'lat' => -6.1680, 'lon' => 106.7600],
            ['kw' => ['jakarta pusat', 'jakpus'], 'zone' => 'JAKPUS', 'sub' => '', 'lat' => -6.1805, 'lon' => 106.8285],
            ['kw' => ['jakarta selatan', 'jaksel'], 'zone' => 'JAKSEL', 'sub' => '', 'lat' => -6.2615, 'lon' => 106.8105],
            ['kw' => ['jakarta timur', 'jaktim'], 'zone' => 'JAKTIM', 'sub' => '', 'lat' => -6.2250, 'lon' => 106.9005],
            ['kw' => ['tangerang selatan', 'tangsel'], 'zone' => 'TANGSEL', 'sub' => '', 'lat' => -6.2885, 'lon' => 106.7180],
            ['kw' => ['tangerang'], 'zone' => 'TANGERANG', 'sub' => '', 'lat' => -6.1785, 'lon' => 106.6320],
            ['kw' => ['bekasi'], 'zone' => 'BEKASI', 'sub' => '', 'lat' => -6.2385, 'lon' => 106.9925],
            ['kw' => ['depok'], 'zone' => 'DEPOK', 'sub' => '', 'lat' => -6.3950, 'lon' => 106.8225],
            ['kw' => ['bogor'], 'zone' => 'BOGOR', 'sub' => '', 'lat' => -6.5950, 'lon' => 106.8060],
        ];
    }

    function dispatchZoneStyle(string $zone): array {
        $styles = [
            'JAKUT'     => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'border' => '#bae6fd'],
            'JAKBAR'    => ['bg' => '#eef2ff', 'color' => '#4338ca', 'border' => '#c7d2fe'],
            'JAKPUS'    => ['bg' => '#f3e8ff', 'color' => '#6d28d9', 'border' => '#ddd6fe'],
            'JAKSEL'    => ['bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0'],
            'JAKTIM'    => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'],
            'TANGERANG' => ['bg' => '#f0fdfa', 'color' => '#0f766e', 'border' => '#99f6e4'],
            'TANGSEL'   => ['bg' => '#f0fdfa', 'color' => '#0f766e', 'border' => '#99f6e4'],
            'BEKASI'    => ['bg' => '#fff1f2', 'color' => '#be123c', 'border' => '#fecdd3'],
            'DEPOK'     => ['bg' => '#f7fee7', 'color' => '#3f6212', 'border' => '#d9f99d'],
            'BOGOR'     => ['bg' => '#f7fee7', 'color' => '#3f6212', 'border' => '#d9f99d'],
        ];
        return $styles[$zone] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'];
    }

    function dispatchIsValidIndoCoord($lat, $lon): bool {
        if ($lat === null || $lon === null || $lat === '' || $lon === '' || !is_numeric($lat) || !is_numeric($lon)) {
            return false;
        }
        $la = floatval($lat);
        $lo = floatval($lon);
        if ($la == 0.0 || $lo == 0.0) return false;
        return ($la >= -11.5 && $la <= 6.5 && $lo >= 94.5 && $lo <= 141.5);
    }

    function dispatchHaversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        return $r * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /**
     * Estimate urban driving distance & duration between two coordinates.
     * shortcut: 1.35x road-winding factor @ 24 km/h Jabodetabek avg; upgraded live in UI via OSRM when reachable.
     */
    function dispatchRoadEstimate($lat1, $lon1, $lat2, $lon2): ?array {
        if (!dispatchIsValidIndoCoord($lat1, $lon1) || !dispatchIsValidIndoCoord($lat2, $lon2)) {
            return null;
        }
        $straightKm = dispatchHaversineKm(floatval($lat1), floatval($lon1), floatval($lat2), floatval($lon2));
        $roadKm = round($straightKm * 1.35, 1);
        $minutes = max(6, (int)round(($roadKm / 24.0) * 60.0 + 4));

        $status = 'lawan_arah';
        if ($roadKm <= 8.5) {
            $status = 'searah';
        } elseif ($roadKm <= 16.5) {
            $status = 'sedang';
        }

        return [
            'km' => $roadKm,
            'minutes' => $minutes,
            'status' => $status,
        ];
    }

    function dispatchJobDurationMin(?string $jenisKegiatan): int {
        $k = strtolower(trim($jenisKegiatan ?? ''));
        if (strpos($k, 'survey') !== false) return 75;
        if (strpos($k, 'pasang') !== false) return 210;
        if (strpos($k, 'service') !== false) return 105;
        return 120;
    }

    /**
     * Resolve Zone Badge + Coordinates (exact or estimated from address/customer coordinates).
     */
    function dispatchResolveZoneAndCoords(
        $lat,
        $lon,
        ?string $alamatLokasi = '',
        ?string $custAlamat = '',
        ?string $custKota = '',
        ?array $fallbackCustCoord = null
    ): array {
        $hasExact = dispatchIsValidIndoCoord($lat, $lon);
        $resLat = $hasExact ? round(floatval($lat), 6) : null;
        $resLon = $hasExact ? round(floatval($lon), 6) : null;
        $coordSource = $hasExact ? 'exact' : 'none';

        if (!$hasExact && $fallbackCustCoord && dispatchIsValidIndoCoord($fallbackCustCoord['lat'] ?? null, $fallbackCustCoord['lon'] ?? null)) {
            $resLat = round(floatval($fallbackCustCoord['lat']), 6);
            $resLon = round(floatval($fallbackCustCoord['lon']), 6);
            $hasExact = true;
            $coordSource = 'cust_saved';
        }

        $haystack = ' ' . strtolower(trim(($alamatLokasi ?? '') . ' ' . ($custAlamat ?? '') . ' ' . ($custKota ?? '') . ' ' . ($fallbackCustCoord['address'] ?? ''))) . ' ';
        $catalog = dispatchGetAreaCatalog();

        $matched = null;
        foreach ($catalog as $entry) {
            foreach ($entry['kw'] as $kw) {
                if (strpos($haystack, $kw) !== false) {
                    $matched = $entry;
                    break 2;
                }
            }
        }

        // If exact coordinates exist and matched only macro-city (or nothing), refine sub-area by nearest centroid <= 7km
        if ($hasExact && ($matched === null || $matched['sub'] === '')) {
            $bestDist = 999.0;
            $bestEntry = null;
            foreach ($catalog as $entry) {
                if ($entry['sub'] === '') continue;
                $d = dispatchHaversineKm($resLat, $resLon, $entry['lat'], $entry['lon']);
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestEntry = $entry;
                }
            }
            if ($bestEntry !== null && $bestDist <= 7.5) {
                $matched = $bestEntry;
            }
        }

        // If no exact coordinates exist, fall back to matched centroid so map routing still works with ~Estimasi tag
        if (!$hasExact && $matched !== null) {
            $resLat = $matched['lat'];
            $resLon = $matched['lon'];
            $coordSource = 'estimated';
        }

        $zone = $matched['zone'] ?? 'LAINNYA';
        $sub = $matched['sub'] ?? '';
        $label = $zone === 'LAINNYA'
            ? 'Lokasi Umum'
            : ($sub !== '' ? ($zone . ' · ' . $sub) : $zone);

        $style = dispatchZoneStyle($zone);

        return [
            'zone' => $zone,
            'sub_area' => $sub,
            'label' => $label,
            'bg' => $style['bg'],
            'color' => $style['color'],
            'border' => $style['border'],
            'lat' => $resLat,
            'lon' => $resLon,
            'is_exact' => ($coordSource === 'exact' || $coordSource === 'cust_saved'),
            'coord_source' => $coordSource,
        ];
    }

    function dispatchRenderZoneBadge(array $zoneInfo): string {
        $lbl = htmlspecialchars($zoneInfo['label'], ENT_QUOTES, 'UTF-8');
        $bg = $zoneInfo['bg'];
        $color = $zoneInfo['color'];
        $border = $zoneInfo['border'];
        $estHint = (!$zoneInfo['is_exact'] && $zoneInfo['lat'] !== null) ? ' title="Estimasi wilayah dari teks alamat"' : '';
        return '<span class="dispatch-zone-pill" data-zone="' . htmlspecialchars($zoneInfo['zone'], ENT_QUOTES, 'UTF-8') . '"' . $estHint .
            ' style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;padding:2.5px 8px;border-radius:999px;background:' . $bg . ';color:' . $color . ';border:1px solid ' . $border . ';white-space:nowrap;letter-spacing:0.02em;">' .
            '📍 ' . $lbl . '</span>';
    }
}

