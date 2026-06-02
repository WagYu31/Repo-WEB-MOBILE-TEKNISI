<?php
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Your message has been successfully delivered.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Under Maintenance</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <?php
    include "head.php";
    ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            width:100vw;
            height:100vh;
        }

        .maintenance-card {
            max-width: 500px;
            margin: 0 auto;
        }

        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-gradient-info">

    <main class="main-content py-4">
        <div class="container">
            <div class="row d-flex justify-content-center align-items-center">
                <div class="col-lg-8 mt-3">
                    <div class="rounded p-4">
                        <h1 class="mb-3 text-center text-white d-md-block d-none" style="font-size: 80px; border-bottom:1px solid white;">MAINTENANCE</h1>
                        <h1 class="mb-3 text-center text-white d-md-none d-block" style="font-size: 30px; border-bottom:1px solid white;">MAINTENANCE</h1>
                        <p class="mb-0 mt-n2 text-center text-white">www.teknisi.loewix.com</p>
                        <p class="mb-4 text-center text-white">We're working hard to improve our website and we'll ready to launch after</p>
                        <div class="countdown mb-4 text-center" style="font-size:35px; color:#fdd224;" id="countdown"></div>
                        <form class="border py-5 px-4 rounded shadow-sm bg-white" action="proses-maintenance.php" method="post">
                            <div class="form-group">
                                <input type="text" class="form-control border p-2 text-dark" placeholder="Your Name" name="nama" required>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control border p-2 text-dark" placeholder="Maintenance Notes" rows="3" name="message" required></textarea>
                            </div>
                            <button type="submit" class="btn bg-gradient-success btn-block">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set the date we're counting down to
        var countDownDate = new Date("March 8, 2024 07:00:00").getTime();

        // Update the countdown every 1 second
        var x = setInterval(function() {

            // Get the current date and time
            var now = new Date().getTime();

            // Calculate the distance between now and the countdown date
            var distance = countDownDate - now;

            // Calculate days, hours, minutes, and seconds
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display the countdown in the element with id="countdown"
            document.getElementById("countdown").innerHTML = days + "d " + hours + "h " +
                minutes + "m " + seconds + "s ";

            // If the countdown is over, display a message
            if (distance < 0) {
                clearInterval(x);
                document.getElementById("countdown").innerHTML = "EXPIRED";
            }
        }, 1000);
    </script>
</body>

</html>