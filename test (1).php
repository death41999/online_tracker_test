<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Step Counter</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        /* Navbar */
        .navbar {
            background-color: #393BB2;
            color: white;
            padding: 15px 20px;
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #393BB2;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }

        .stats p {
            text-align: center;
            font-size: 1.2em;
            margin: 0;
        }

        .stats span {
            display: block;
            font-size: 1.5em;
            font-weight: bold;
            color: #393BB2;
        }

        #status {
            text-align: center;
            font-style: italic;
            color: #666;
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .navbar {
                font-size: 1.2em;
                padding: 10px;
            }

            .container {
                margin: 10px;
                padding: 15px;
            }

            .stats {
                flex-direction: column;
            }

            .stats p {
                margin: 10px 0;
            }
        }
		       /* Footer */
        footer {
            background-color: #393BB2;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.9em;
            margin-top: auto;
        }

    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
       Web based Step Tracker
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1>Track Your Steps</h1>

        <!-- Input Fields -->
        <label for="weight">Enter Weight (kg):</label>
        <input type="number" id="weight" value="70" min="20" max="200">

        <label for="height">Enter Height (cm):</label>
        <input type="number" id="height" value="170" min="100" max="250">

        <!-- Stats Display -->
        <div class="stats">
            <p>Steps: <span id="steps">0</span></p>
            <p>Calories: <span id="calories">0</span> kcal</p>
            <p>Speed: <span id="speed">0</span> m/s</p>
            <p>BMI: <span id="bmi">0</span></p>
        </div>

        <!-- Status Message -->
        <p id="status">Waiting for motion...</p>
    </div>
    <!-- Footer -->
    <footer>
        Developed by: Charles
    </footer>
    <script>
        let stepCount = 0;
        let lastAccY = null;
        let lastStepTime = null;
        let weight = document.getElementById('weight').value;
        let height = document.getElementById('height').value;
        let wakeLock = null;
        let inactivityTimer = null;
        const INACTIVITY_THRESHOLD = 2000; // 2 seconds

        // Update weight and height on input
        document.getElementById('weight').addEventListener('input', function() {
            weight = this.value;
            updateBMI(); // Recalculate BMI when weight changes
        });

        document.getElementById('height').addEventListener('input', function() {
            height = this.value;
            updateBMI(); // Recalculate BMI when height changes
        });

        // Calculate BMI
        function calculateBMI(weight, height) {
            // Convert height from cm to meters
            let heightInMeters = height / 100;
            // BMI formula: weight (kg) / (height (m) * height (m))
            return (weight / (heightInMeters * heightInMeters)).toFixed(2);
        }

        // Update BMI display
        function updateBMI() {
            let bmi = calculateBMI(weight, height);
            document.getElementById('bmi').textContent = bmi;
        }

        // Calculate step length based on height
        function getStepLength() {
            return (height * 0.415) / 100; // Approx stride length in meters
        }

        // Update calories burned
        function updateCalories() {
            let stepLength = getStepLength();
            let caloriesPerStep = (weight * 0.0005 * stepLength); // Energy spent per step
            let totalCalories = stepCount * caloriesPerStep;
            document.getElementById('calories').textContent = totalCalories.toFixed(2);
        }

        // Update speed
        function updateSpeed() {
            let currentTime = Date.now();
            if (lastStepTime) {
                let stepTime = (currentTime - lastStepTime) / 1000; // Convert ms to sec
                let speed = getStepLength() / stepTime; // m/s
                document.getElementById('speed').textContent = speed.toFixed(2);
            }
            lastStepTime = currentTime;

            // Reset inactivity timer
            resetInactivityTimer();
        }

        // Detect steps
        function stepDetected() {
            stepCount++;
            document.getElementById('steps').textContent = stepCount;
            updateCalories();
            updateSpeed();
        }

        // Reset speed to zero if no steps are detected for INACTIVITY_THRESHOLD
        function resetInactivityTimer() {
            if (inactivityTimer) {
                clearTimeout(inactivityTimer);
            }
            inactivityTimer = setTimeout(() => {
                document.getElementById('speed').textContent = "0.00";
            }, INACTIVITY_THRESHOLD);
        }

        // Keep screen on
        async function requestWakeLock() {
            if ('wakeLock' in navigator) {
                try {
                    wakeLock = await navigator.wakeLock.request('screen');
                    console.log("Screen wake lock enabled");
                    wakeLock.addEventListener('release', () => {
                        console.log("Wake Lock Released");
                    });
                } catch (err) {
                    console.error("Wake Lock Error:", err);
                }
            }
        }

        // Detect motion with Accelerometer
        if ('Accelerometer' in window) {
            try {
                let sensor = new Accelerometer({ frequency: 20 });
                sensor.addEventListener('reading', () => {
                    let y = sensor.y;
                    let threshold = 1.5; // Adjust threshold based on testing

                    if (lastAccY !== null && Math.abs(y - lastAccY) > threshold) {
                        stepDetected();
                    }
                    lastAccY = y;
                });

                sensor.start();
                document.getElementById('status').textContent = "Accelerometer Active";
                requestWakeLock(); // Keep screen on
            } catch (error) {
                console.error("Accelerometer API error:", error);
                document.getElementById('status').textContent = "Device not supported!";
            }
        } else {
            document.getElementById('status').textContent = "Accelerometer not available!";
        }

        // Handle wake lock loss
        document.addEventListener("visibilitychange", () => {
            if (wakeLock !== null && document.visibilityState === "visible") {
                requestWakeLock();
            }
        });

        // Initial BMI calculation
        updateBMI();
    </script>

</body>
</html>