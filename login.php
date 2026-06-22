<?php
session_start();
include 'include/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT *
            FROM users
            WHERE username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_designation'];

            // Redirect based on role
            if ($user['role_designation'] == 'admin') {

                header("Location: ../new project/admin/index.php");

            } elseif ($user['role_designation'] == 'it_support') {

                header("Location: ../new project/IT_Officer/index.php");

            } elseif ($user['role_designation'] == 'reception') {

                header("Location: ../reception/index.php");

            } elseif ($user['role_designation'] == 'manager') {

                header("Location: ../manager/index.php");

            } else {

                header("Location: ../index.php");
            }

            exit();

        } else {

            echo "
            <h2 style='color:red'>Invalid Password!</h2>
            <p>Redirecting in 5 seconds...</p>

            <script>
            setTimeout(function(){
                window.location.href='login.html';
            },5000);
            </script>";
        }

    } else {

        echo "
        <h2 style='color:red'>Username Not Found!</h2>
        <p>Redirecting in 5 seconds...</p>

        <script>
        setTimeout(function(){
            window.location.href='login.html';
        },5000);
        </script>";
    }

    $stmt->close();
    $conn->close();
}
?>