    <?php
    $conn2 = new mysqli("localhost", "u836263092_rootBukti", "Eddie@18", "u836263092_bukti");
    if ($conn2->connect_error) {
        die("Connection failed: " . $conn2->connect_error);
    }
    
    $sql2 = "SELECT bukti, teknisi, service, quotation FROM users WHERE name = '$nmUser'";
    $result2 = $conn2->query($sql2);
    
    $permissions = ["bukti" => "N", "teknisi" => "N", "service" => "N", "quotation" => "N"];
    if ($result2->num_rows > 0) {
        $permissions = $result2->fetch_assoc();
    }
    $conn2->close();
    ?>

    <div class="floating-menu no-print">
        <div class="submenu">
                <a href="https://center.grav-tech.com/center-login.php?nama=<?php echo $nmUser; ?>" class="menu-item" target="_blank" title="Bukti"><span class="material-symbols-outlined fs-4">business_center</span> Center</a>
            <?php if ($permissions['bukti'] == 'Y') { ?>
                <a href="https://bukti.grav-tech.com/center-login.php?nama=<?php echo $nmUser; ?>" class="menu-item" target="_blank" title="Bukti"><span class="material-symbols-outlined fs-4">favorite</span> Bukti</a>
            <?php } ?>
            <?php if ($permissions['teknisi'] == 'Y') { ?>
                <a href="https://jadwal.grav-tech.com/center-login.php?nama=<?php echo $nmUser; ?>" class="menu-item" target="_blank" title="Teknisi"><span class="material-symbols-outlined fs-4">engineering</span> Teknisi</a>
            <?php } ?>
            <?php if ($permissions['service'] == 'Y') { ?>
                <a href="https://service.grav-tech.com/src/html/process/center-login.php?nama=<?php echo $nmUser; ?>" target="_blank" class="menu-item" title="Service"><span class="material-symbols-outlined fs-4">shield_person</span> Service</a>
            <?php } ?>
            <?php if ($permissions['quotation'] == 'Y') { ?>
                <a href="https://quo.grav-tech.com/center-login.php?nama=<?php echo $nmUser; ?>" class="menu-item" target="_blank" title="Quotation"><span class="material-symbols-outlined fs-4">rubric</span> Quotation</a>
            <?php } ?>
        </div>
        <div class="menu-btn"><span class="material-symbols-outlined fs-8">favorite</span></div>
    </div>

    <script>
        $(document).ready(function () {
            $(".menu-btn").click(function () {
                $(".submenu").toggleClass("show");
            });
        });
    </script>