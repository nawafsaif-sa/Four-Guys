<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/db.php";

function out($ok, $msg = "", $extra = []) {
  echo json_encode(array_merge(["ok"=>$ok, "msg"=>$msg], $extra));
  exit;
}

/* تأكيد تسجيل العميل */
if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  out(false, "Unauthorized");
}

$user_id = (int)$_SESSION['client_id'];

$review_id = (int)($_POST['review_id'] ?? 0);
$vote      = $_POST['vote'] ?? '';

if ($review_id <= 0 || !in_array($vote, ['like','dislike'], true)) {
  out(false, "Invalid data");
}

/* تحقق من وجود الريفيو */
$stmt = mysqli_prepare($conn, "SELECT id FROM product_reviews WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $tmp);
if (!mysqli_stmt_fetch($stmt)) {
  out(false, "Review not found");
}
mysqli_stmt_close($stmt);

/* تحقق هل المستخدم مصوّت */
$stmt = mysqli_prepare($conn,
  "SELECT id, vote FROM review_votes WHERE review_id=? AND user_id=?"
);
mysqli_stmt_bind_param($stmt, "ii", $review_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $existing_id, $existing_vote);

if (mysqli_stmt_fetch($stmt)) {
  mysqli_stmt_close($stmt);

  if ($existing_vote === $vote) {
    $stmt = mysqli_prepare($conn, "DELETE FROM review_votes WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $existing_id);
    mysqli_stmt_execute($stmt);
  } else {
    $stmt = mysqli_prepare($conn, "UPDATE review_votes SET vote=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $vote, $existing_id);
    mysqli_stmt_execute($stmt);
  }
} else {
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare($conn,
    "INSERT INTO review_votes (review_id, user_id, vote) VALUES (?,?,?)"
  );
  mysqli_stmt_bind_param($stmt, "iis", $review_id, $user_id, $vote);
  mysqli_stmt_execute($stmt);
}

/* العدّادات */
$stmt = mysqli_prepare($conn,
  "SELECT
     SUM(vote='like'),
     SUM(vote='dislike')
   FROM review_votes
   WHERE review_id=?"
);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $likes, $dislikes);
mysqli_stmt_fetch($stmt);

out(true, "OK", [
  "likes"    => (int)$likes,
  "dislikes" => (int)$dislikes
]);