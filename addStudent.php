<?php
require_once 'config.php'; // DB connection

// Initialize variables
$schools = [];
$years = [];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Fetch schools
$sql1 = "SELECT School_Names FROM ATGET_School_Names ORDER BY School_Names ASC";
$result1 = $conn->query($sql1);
if ($result1 && $result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $schools[] = $row['School_Names'];
    }
}

// Fetch academic years
$sql2 = "SELECT Academic_Year FROM ATGET_Academic_Year ORDER BY Academic_Year ASC";
$result2 = $conn->query($sql2);
if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $years[] = $row['Academic_Year'];
    }
}

// Handle form submission
if ($action === 'insert') {
    // Get form data
    $studentName = $_POST['studentName'];
    $parentName = $_POST['parentName'];
    $contactNumber = $_POST['contactDetails'];
    $aadharNumber = $_POST['aadharNumber'];
    $address = $_POST['address'];
    $schoolName = $_POST['schoolName'];
    
    $academicYearRaw = $_POST['academicYear'] ?? '';
    $academicYear  = str_replace('-', '', $academicYearRaw);

    //$academicYear = $_POST['academicYear'];

    // STEP 1: Get Campus_No
    $campusSql = "SELECT Campus_No FROM ATGET_School_Names WHERE School_Names = ?";
    $campusStmt = $conn->prepare($campusSql);
    $campusStmt->bind_param("s", $schoolName);
    $campusStmt->execute();
    $campusResult = $campusStmt->get_result();

    if (!$campusResult || $campusResult->num_rows === 0) {
        echo "<script>alert('Invalid School Name.'); window.history.back();</script>";
        exit();
    }

    $campusRow = $campusResult->fetch_assoc();
    $campusNo = str_pad($campusRow['Campus_No'], 3, '0', STR_PAD_LEFT); // 3 digits

    // STEP 2: Generate count based on prefix
    $prefix = $academicYear . $campusNo; // 9-digit prefix
    $countSql = "SELECT COUNT(*) as total FROM ATGET_Students_Details WHERE Admission_No LIKE CONCAT(?, '%')";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $prefix);
    $countStmt->execute();
    $countResult = $countStmt->get_result();

    $totalCount = 0;
    if ($countResult && $row = $countResult->fetch_assoc()) {
        $totalCount = intval($row['total']) + 1;
    }

    $countPadded = str_pad($totalCount, 5, '0', STR_PAD_LEFT); // Remaining 5 digits
    $admissionNo = $prefix . $countPadded; // Final 14-digit Admission No

    // STEP 3: Insert into DB
    $courseCompletionYear = 'ACTIVE';

    $sql = "INSERT INTO ATGET_Students_Details 
        (Admission_No, Student_Name, Parent_Name, Contact_No, Student_Aadhar_No, Student_Address, School_Name, Admission_Year, Course_Completion_Academic_Year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $admissionNo, $studentName, $parentName, $contactNumber, $aadharNumber, $address, $schoolName, $academicYear, $courseCompletionYear);

    if ($stmt->execute()) {
        echo "<script>alert('Student added successfully. Admission No: $admissionNo'); window.location.href = 'addStudent.php';</script>";
    } else {
        echo "<script>alert('Error adding student: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $countStmt->close();
    $campusStmt->close();
    $conn->close();
    ob_end_flush();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Registration Form</title>
    <style>
        /* Same style as before */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
            line-height: 1.5;
            min-height: 100vh;
            background: #f3f3f3;
            flex-direction: column;
            margin: 0;
        }

        .main {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            padding: 30px 20px;
            width: 90%;
            max-width: 500px;
        }

        ul {
            list-style-type: none;
            margin: 0 0 20px 0;
            padding: 0;
            overflow: hidden;
            background-color: #333;
            border-radius: 10px;
        }

        li {
            float: left;
        }

        li a {
            display: block;
            color: white;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
        }

        li a:hover:not(.active) {
            background-color: #111;
        }

        .active {
            background-color: #04AA6D;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
        }

        button {
            padding: 10px;
            border-radius: 10px;
            border: none;
            color: white;
            cursor: pointer;
            background-color: #4CAF50;
            font-size: 16px;
            margin: 5px;
            width: 48%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .submit-section {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        @media (max-width: 500px) {
            .submit-section {
                flex-direction: column;
                align-items: center;
            }

            button {
                width: 100%;
                margin: 8px 0;
            }

            ul li a {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="main">
    <!-- Navigation Bar -->
    <ul>
        <li style="float:right"><a class="active" href="../login.html">Logout</a></li>
    </ul>

    <!-- Student Registration Form -->
    <form action="addStudent.php" method="POST" id="studentForm">
        <input type="hidden" name="action" value="insert" />

        <h2>Student Registration Form</h2>

        <label for="studentName">Student Name:</label>
        <input type="text" id="studentName" name="studentName" required />

        <label for="parentName">Parent Name:</label>
        <input type="text" id="parentName" name="parentName" required />

        <label for="contactDetails">Contact Number:</label>
        <input type="tel" id="contactDetails" name="contactDetails" required placeholder="Enter phone number" maxlength="10" pattern="\d{10}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />

        <label for="aadharNumber">Aadhar Number:</label>
        <input type="text" id="aadharNumber" name="aadharNumber" required maxlength="12" pattern="\d{12}" placeholder="12-digit Aadhar Number" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"/>

        <label for="address">Address:</label>
        <textarea id="address" name="address" required placeholder="Enter address..."></textarea>

        <label for="schoolName">School/College Name:</label>
        <select id="schoolName" name="schoolName" required>
            <option value="" disabled selected>-- Select School/College --</option>
            <?php foreach ($schools as $school): ?>
                <option value="<?php echo htmlspecialchars($school); ?>">
                    <?php echo htmlspecialchars($school); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="academicYear">Select Academic Year:</label>
        <select id="academicYear" name="academicYear" required>
            <option value="" disabled selected>-- Select Academic Year --</option>
            <?php foreach ($years as $year): ?>
                <option value="<?php echo htmlspecialchars($year); ?>">
                    <?php echo htmlspecialchars($year); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="submit-section">
            <button type="button" onclick="loadHomePage()">Cancel</button>
            <button type="submit">Submit</button>
        </div>
    </form>
</div>

<script>
    // Redirect to homepage if Cancel button is clicked
    function loadHomePage() {
        window.location.href = 'atgethomepage.html';  // Redirect to homepage
    }
</script>

</body>
</html>
