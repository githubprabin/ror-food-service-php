<?php
include("config.php");
header("Access-Control-Allow-Origin: *");

/**********************ADMIN**********************/
if(isset($_POST['menu'])){

  $menu = $_POST['menu'];
  $menu = json_decode($menu, true);

  mysqli_query($con, "DELETE FROM `food-menu` WHERE 1");
  foreach($menu as $item) {
    $key = $item['key'];
    $itemName = $item['itemName'];
    $itemPrice = $item['itemPrice'];
    $isAvailable = $item['isAvailable'];

    echo $itemName;
    
    $query = "INSERT INTO `food-menu`(`id`, `itemName`, `itemPrice`, `isAvailable`) 
    VALUES ('".$key."','".$itemName."','".$itemPrice."','".$isAvailable."')";

    mysqli_query($con,$query);
  }
}

if(isset($_GET['get-menu'])) {
  $menu = [];
  $query = "SELECT * FROM `food-menu` WHERE 1";
  $result = $con->query($query);

  if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

      $item = new \stdClass();
      $item->key = $row['id'];
      $item->itemName = $row['itemName'];
      $item->itemPrice = $row['itemPrice'];
      $item->isAvailable = $row['isAvailable'];
       
      array_push($menu, $item);
    }
    // print_r($menu);
    $jsonItem = json_encode($menu);
    print_r($jsonItem);    
  }
}

if(isset($_GET['request-list'])) {
  date_default_timezone_set("Asia/Kathmandu");
  $date = date("Y-m-d");
  
  $list = [];
  $query = "SELECT `requestID`, `userID` , `time`, `isComplete` FROM `request-list` WHERE `date`>='".$date."'";
  $result = $con->query($query);

  if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

      $item = new \stdClass();
      
      $userID = $row['userID'];
      $requestID = $row['requestID'];
      $item->requestID = $requestID;
      
      //get user detail        
      $user = $con->query("SELECT * FROM `users` WHERE `userID` =".$userID)->fetch_assoc();
      $item->userName = $user['name'];
      $item->userEmail = $user['email'];
      $item->isComplete = $row['isComplete'];

      //get time requested
      $item->requestTime = $row['time'];
      
      $orderQuery = "SELECT `itemName`, `itemPrice`, `quantity` FROM `orders` WHERE `requestID`=".$requestID;
      $ordersResult = $con->query($orderQuery);
      
      //get ordered items
      $orderList = [];
      if($ordersResult->num_rows > 0) {
        while($o_row = $ordersResult->fetch_assoc()) {
          $orderItem = new \stdClass();
          $orderItem->itemName = $o_row['itemName'];
          $orderItem->itemPrice = $o_row['itemPrice'];
          $orderItem->quantity = $o_row['quantity'];

          array_push($orderList, $orderItem);
        }
      }
      $item->orderedItems = $orderList;
      array_push($list, $item);
    }
  }
  $jsonData = json_encode($list);
  print_r($jsonData);
}

if(isset($_POST['remove-request'])) {
  $requestID = $_POST['requestID'];
  $success = $con->query("UPDATE `request-list` SET `isComplete`= 1 WHERE `requestID` = ".$requestID);
  if($success) {
    print_r ('Request has been marked as completed');
  } else {
    print_r ('Action failed');
  }
}

/**********************CLIENT**********************/
if(isset($_POST['request-item'])) {
  $user = $_POST['user'];
  $email = $_POST['email'];
  $isComplete = $_POST['isComplete'];
  
  //get user id from table users
  $user = $con->query("SELECT `userID` FROM `users` WHERE `email` = '".$email."'");
  $userID = $user->fetch_assoc()['userID'];

  //insert request to request table and get requset id
  date_default_timezone_set("Asia/Kathmandu");
  $date = date("Y-m-d");
  $time = date("h:i:sa");

  $query = "INSERT INTO `request-list`(`date`, `time`, `userID`, `isComplete`) 
    VALUES ('".$date."','".$time."','".$userID."','".$isComplete."')";
  mysqli_query($con,$query);
  $requestID = mysqli_insert_id($con);

  //add the items ordered
  $orderedItems = $_POST['orderedItems'];
  $orderedItems = json_decode($orderedItems, true);

  foreach($orderedItems as $item) {
    $itemName = $item['itemName'];
    $itemPrice = $item['itemRate'];
    $quantity = $item['quantityOrdered'];
    echo $itemName;

    $query = "INSERT INTO `orders`(`itemName`, `itemPrice`, `quantity`, `requestID`)
    VALUES ('".$itemName."',".$itemPrice.",".$quantity.",".$requestID.")";

    mysqli_query($con,$query);
  } 
}

if(isset($_POST['signup-user'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  $success = $con->query("INSERT INTO `users`(`name`, `email`, `password`) VALUES ('".$name."','".$email."','".$password."')");
  if($success) {
    $response = new \stdClass();
    $response->message = 'Sign up successful';
    $response->success = 1;
    print_r(json_encode($response));
  } else {
    $response = new \stdClass();
    $response->message = 'Email has already been registered';
    $response->success = 0;
    print_r(json_encode($response));
  }
}

if(isset($_POST['login-user'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $result = $con->query("SELECT * FROM `users` WHERE `email` ='".$email."' && `password` ='".$password."'");
  if($result->num_rows > 0) {
    $response = new \stdClass();
    $response->message = 'Success';
    $response->success = 1;
    $response->userName = $result->fetch_assoc()['name'];
    print_r(json_encode($response));
  } else {
    $response = new \stdClass();
    $response->message = 'Invalid email or password';
    $response->success = 0;
    $response->userName = '';
    print_r(json_encode($response));
  }
}

?>


