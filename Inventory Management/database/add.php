<?php
    //start the session.
    session_start();

    $table_name = $_SESSION['table'];
    $_SESSION['table'] = '';

    $first_name = $_POST['firstname'];
    $last_name = $_POST['lastname'];
    $email = $_POST['email'];
    $phone_no = $_POST['phone'];
    $password = $_POST['password'];
    $encrypted = password_hash($password, PASSWORD_DEFAULT);

    //Adding the record.
    try{
        $command = "INSERT INTO
                            $table_name(first_name, last_name, email, password, phone_no, created_at, updated_at)
                        Values
                             ('".$first_name."', '".$last_name."', '".$email."', '".$phone_no."', '".$encrypted."',Now(),Now())";
        include('connection.php');
        $conn->exec($command);
        $response = [
            'success' => true,
            'message' => $first_name . ''.$last_name. 'successfully added to the system.'
        ];

    } catch (PDOException $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }

    $_SESSION['response'] = $response;
    header('location:../register.php');
?>
