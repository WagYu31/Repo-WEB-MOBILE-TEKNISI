<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=PT+Sans:ital,wght@0,400;0,700;1,400;1,700&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gruppo&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=PT+Sans:ital,wght@0,400;0,700;1,400;1,700&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: "Inter", sans-serif;
            color: white;
            background: url('assets/background-image.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .content {
            /* background: rgba(0, 0, 0, 0.7);
            height:100%; */
            padding: 20px;
            border-radius: 10px;
        }

        .countdown {
            font-size: 2em;
            font-family: "PT Sans", sans-serif;
        }

        footer {
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
        }

        .textWeb {
            font-size: 0.9em;
        }

        .textLwx {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: -3px;
        }

        .localBrand {
            font-size: 0.9em;
        }

        @media screen and (max-width: 768px) {
            .countdown {
                font-size: 1.5em;
            }

            .content {
                margin-top: -5vh;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="content d-flex flex-column justify-content-center align-items-center">
            <h1>Coming Soon</h1>
            <div class="countdown mt-4 mb-md-3 mb-4">
                <span id="days">00</span> days <span style="font-weight: 400; font-family: 'Gruppo', sans-serif; font-size:0.8em;">/</span>
                <span id="hours">00</span> hours <span style="font-weight: 400; font-family: 'Gruppo', sans-serif; font-size:0.8em;">/</span>
                <span id="minutes">00</span> minutes <span style="font-weight: 400; font-family: 'Gruppo', sans-serif; font-size:0.8em;">/</span>
                <span id="seconds">00</span> seconds
            </div>
            <p class="textWeb col-md-8 col-12">Website ini sedang dalam tahap pengembangan untuk memberikan pengalaman terbaik bagi Anda. Kami menghargai kesabaran dan dukungan Anda. Kunjungi kembali dalam waktu dekat untuk menikmati fitur-fitur menarik yang kami siapkan.</p>
        </div>
    </div>
    <footer>
        <p class="textLwx">Loewix 2024</p>
        <p class="localBrand">#1 Indonesia Local Brand</p>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script for countdown
        function countdown() {
            var now = new Date();
            var eventDate = new Date('September 14, 2024 00:00:00'); // set event date to 1st September 2024
            var currentTime = now.getTime();
            var eventTime = eventDate.getTime();
            var remTime = eventTime - currentTime;

            var s = Math.floor(remTime / 1000);
            var m = Math.floor(s / 60);
            var h = Math.floor(m / 60);
            var d = Math.floor(h / 24);

            h %= 24;
            m %= 60;
            s %= 60;

            document.getElementById("days").textContent = d < 10 ? '0' + d : d;
            document.getElementById("hours").textContent = h < 10 ? '0' + h : h;
            document.getElementById("minutes").textContent = m < 10 ? '0' + m : m;
            document.getElementById("seconds").textContent = s < 10 ? '0' + s : s;

            setTimeout(countdown, 1000);
        }
        countdown();
    </script>
</body>

</html>