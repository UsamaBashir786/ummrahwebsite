<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch flight bookings
$flights_query = $conn->prepare("
    SELECT fb.id, fb.flight_id, fb.cabin_class, fb.total_price, fb.booking_status, fb.created_at, 
           f.airline_name, f.flight_number, f.departure_city, f.arrival_city, f.departure_date, f.arrival_date
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.user_id = ?
    ORDER BY fb.created_at DESC
");
$flights_query->bind_param("i", $user_id);
$flights_query->execute();
$flights = $flights_query->get_result()->fetch_all(MYSQLI_ASSOC);
$flights_query->close();

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    
    $check_stmt = $conn->prepare("SELECT id FROM flight_bookings WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $cancel_stmt = $conn->prepare("UPDATE flight_bookings SET booking_status = 'cancelled' WHERE id = ? AND user_id = ?");
        $cancel_stmt->bind_param("ii", $booking_id, $user_id);

        if ($cancel_stmt->execute()) {
            $_SESSION['booking_message'] = "Flight booking successfully cancelled.";
            $_SESSION['booking_message_type'] = "success";
        } else {
            $_SESSION['booking_message'] = "Error cancelling booking. Please try again.";
            $_SESSION['booking_message_type'] = "error";
        }
        $cancel_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: flights.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Flight Bookings - UmrahFlights</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .booking-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-confirmed { background-color: #d1fae5; color: #059669; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
        .status-completed { background-color: #e5e7eb; color: #4b5563; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content-area">
        <!-- Top Header -->
        <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">My Flight Bookings</h1>
                    <p class="text-gray-600">Manage and track your flight reservations</p>
                </div>
                <a href="../flights.php" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
                    <i class="fas fa-plus mr-2"></i>Book New Flight
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <select id="statusFilter" class="form-select rounded-lg border-gray-300">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
                <input type="text" id="searchInput" class="form-control rounded-lg border-gray-300" placeholder="Search by airline, flight number, or city...">
            </div>
        </div>

        <!-- Flight Bookings Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($flights)): ?>
                <div class="col-span-full text-center py-12">
                    <img src="assets/images/no-flights.svg" alt="No flights" class="w-48 h-48 mx-auto mb-4">
                    <p class="text-gray-500 text-lg">No flight bookings found.</p>
                    <a href="../flights.php" class="mt-4 inline-block bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition">
                        Book Your First Flight
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($flights as $flight): ?>
                    <div class="booking-card bg-white p-6" data-status="<?php echo htmlspecialchars($flight['booking_status']); ?>">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($flight['airline_name']); ?></h3>
                            <span class="status-badge status-<?php echo htmlspecialchars($flight['booking_status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($flight['booking_status'])); ?>
                            </span>
                        </div>
                        <p class="text-gray-600 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Flight: <?php echo htmlspecialchars($flight['flight_number']); ?>
                        </p>
                        <p class="text-gray-600 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <?php echo htmlspecialchars($flight['departure_city']); ?> → <?php echo htmlspecialchars($flight['arrival_city']); ?>
                        </p>
                        <p class="text-gray-600 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <?php echo date('d M Y, H:i', strtotime($flight['departure_date'])); ?>
                        </p>
                        <p class="text-gray-600 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            <?php echo ucfirst(htmlspecialchars($flight['cabin_class'])); ?> Class
                        </p>
                        <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($flight['total_price'], 2); ?></p>
                        <div class="flex gap-3">
                            <button class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium" 
                                    data-bs-toggle="modal" data-bs-target="#detailsModal" 
                                    onclick="showFlightDetails(<?php echo htmlspecialchars(json_encode($flight), ENT_QUOTES, 'UTF-8'); ?>)">
                                View Details
                            </button>
                            <?php if ($flight['booking_status'] == 'pending'): ?>
                                <form method="POST" class="cancel-form">
                                    <input type="hidden" name="booking_id" value="<?php echo $flight['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">
                                        Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-to-r from-cyan-600 to-teal-500 text-white">
                    <h5 class="modal-title text-xl font-bold" id="detailsModalLabel">Flight Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent"></div>
                <div class="modal-footer">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-xl font-medium" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');
        const bookingCards = document.querySelectorAll('.booking-card');

        function filterBookings() {
            const status = statusFilter.value.toLowerCase();
            const search = searchInput.value.toLowerCase();

            bookingCards.forEach(card => {
                const cardStatus = card.dataset.status.toLowerCase();
                const cardText = card.textContent.toLowerCase();

                const statusMatch = !status || cardStatus === status;
                const searchMatch = !search || cardText.includes(search);

                card.style.display = statusMatch && searchMatch ? 'block' : 'none';
            });
        }

        statusFilter.addEventListener('change', filterBookings);
        searchInput.addEventListener('input', filterBookings);

        // Show flight details
        function showFlightDetails(flight) {
            document.getElementById('detailsModalLabel').textContent = `${flight.airline_name} - ${flight.flight_number} Details`;
            document.getElementById('modalContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Flight Information</h6>
                        <p><strong>Airline:</strong> ${flight.airline_name}</p>
                        <p><strong>Flight Number:</strong> ${flight.flight_number}</p>
                        <p><strong>Cabin Class:</strong> ${flight.cabin_class.charAt(0).toUpperCase() + flight.cabin_class.slice(1)}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${flight.booking_status}">${flight.booking_status.charAt(0).toUpperCase() + flight.booking_status.slice(1)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Route & Schedule</h6>
                        <p><strong>From:</strong> ${flight.departure_city}</p>
                        <p><strong>To:</strong> ${flight.arrival_city}</p>
                        <p><strong>Departure:</strong> ${new Date(flight.departure_date).toLocaleString()}</p>
                        <p><strong>Arrival:</strong> ${new Date(flight.arrival_date).toLocaleString()}</p>
                    </div>
                </div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h6 class="font-bold mb-3">Payment Details</h6>
                        <p><strong>Total Price:</strong> Rs${parseFloat(flight.total_price).toFixed(2)}</p>
                        <p><strong>Booked On:</strong> ${new Date(flight.created_at).toLocaleString()}</p>
                    </div>
                </div>
            `;
        }

        // Cancel booking confirmation
        document.querySelectorAll('.cancel-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This flight booking will be cancelled.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, cancel it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Show booking message
        <?php if (isset($_SESSION['booking_message'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['booking_message_type']; ?>',
                title: '<?php echo $_SESSION['booking_message_type'] == 'success' ? 'Success!' : 'Error!'; ?>',
                text: '<?php echo $_SESSION['booking_message']; ?>',
                confirmButtonColor: '#06b6d4'
            });
            <?php 
                unset($_SESSION['booking_message']);
                unset($_SESSION['booking_message_type']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html>