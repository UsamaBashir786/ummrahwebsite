<?php
// includes/contact-functions.php

function getContactInfo($conn, $type = null, $primary_only = false)
{
  $where_conditions = ["status = 'active'"];
  $params = [];
  $types = "";

  if ($type) {
    $where_conditions[] = "type = ?";
    $params[] = $type;
    $types .= "s";
  }

  if ($primary_only) {
    $where_conditions[] = "is_primary = 1";
  }

  $query = "SELECT * FROM contact_info WHERE " . implode(" AND ", $where_conditions);
  $query .= " ORDER BY is_primary DESC, created_at ASC";

  $stmt = $conn->prepare($query);
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();

  $contact_info = [];
  while ($row = $result->fetch_assoc()) {
    $contact_info[] = $row;
  }

  return $contact_info;
}

function getPrimaryContact($conn, $type)
{
  $contacts = getContactInfo($conn, $type, true);
  return !empty($contacts) ? $contacts[0] : null;
}

function getContactsByType($conn, $type)
{
  return getContactInfo($conn, $type);
}
?>