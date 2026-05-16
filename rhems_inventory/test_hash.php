<?php
$password = 'admin123';
$hash = '$2y$10$3LBNV7P6g91xeRZ8h2RXdOe0Ijw/fQGu4TDuN0bkC/c9g9RuR7ixC';

if (password_verify($password, $hash)) {
    echo "Password is correct.";
} else {
    echo "Wrong password.";
}
?>
