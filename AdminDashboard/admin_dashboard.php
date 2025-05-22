<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php"); // Redirect to login page if not logged in
    exit();
}

// Get the logged-in user's name
$user_name = $_SESSION['user']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- My CSS -->
	<link rel="stylesheet" href="style.css">

	<title>Shop-Seva</title>
</head>
<body>


	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-smile'></i>
			<span class="text">Shop-Seva</span>
		</a>
		<ul class="side-menu top">
			<li class="active">
				<a href="#">
					<i class='bx bxs-dashboard' ></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-group' ></i>
					<span class="text">Staff</span>
				</a>
			</li>
			<li>
				<a href="products.php">
					<i class='bx bxs-shopping-bag-alt' ></i>
					<span class="text">Product</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-doughnut-chart' ></i>
					<span class="text">Analytics</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-message-dots' ></i>
					<span class="text">Message</span>
				</a>
			</li>
			
		</ul>
		<ul class="side-menu">
			<li>
				<a href="#">
					<i class='bx bxs-cog' ></i>
					<span class="text">Settings</span>
				</a>
			</li>
			<li>
				<a href="../logout.php" class="logout">
					<i class='bx bxs-log-out-circle' ></i>
					<span class="text">Logout</span>
				</a>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			<!-- <a href="#" class="nav-link">Categories</a> -->
			<form action="#">
				<div class="form-input">
					<input type="search" placeholder="Search...">
					<button type="submit" class="search-btn"><i class='bx bx-search' ></i></button>
				</div>
			</form>
			<span class="text">
    <?php echo htmlspecialchars($user_name); ?>
</span>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			<!-- <a href="#" class="notification">
				<i class='bx bxs-bell' ></i>
				<span class="num">8</span>
			</a> -->
			<a href="#" class="profile">
				<img src="img/profile.png">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		<main>
			<div class="head-title">
				<div class="left">
					<h1>Dashboard</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Home</a>
						</li>
					</ul>
				</div>
				<!-- <a href="#" class="btn-download">
					<i class='bx bxs-cloud-download' ></i>
					<span class="text">Download PDF</span>
				</a> -->
			</div>

			<ul class="box-info">
				<li>
					<i class='bx bx-rupee' ></i>
					<span class="text">
						<h3>1020</h3>
						<p>Today Profits</p>
					</span>
				</li>
				<li>
					<i class='bx bx-cart' ></i>
					<span class="text">
						<h3>34</h3>
						<p>Today Sales</p>
					</span>
				</li>
				<li>
					<i class='bx bxs-group' ></i>
					<span class="text">
						<h3>12</h3>
						<p>Today Custamor</p>
					</span>
				</li>
			</ul>
			<ul class="box-info">
				<li>
					<i class='bx bx-rupee' ></i>
					<span class="text">
						<h3>9020</h3>
						<p>This Month Profits</p>
					</span>
				</li>
				<li>
					<i class='bx bx-cart' ></i>
					<span class="text">
						<h3>934</h3>
						<p>This Month Sales</p>
					</span>
				</li>
				<li>
					<i class='bx bxs-group' ></i>
					<span class="text">
						<h3>342</h3>
						<p>This Month Custamor</p>
					</span>
				</li>
			</ul>


			<div class="table-data">
				<div class="order">
					<div class="head">
						<h3>Recent Bills</h3>
						<!-- <i class='bx bx-search' ></i>
						<i class='bx bx-filter' ></i> -->
					</div>
					<table>
						<thead>
							<tr>
								<th>Custamor</th>
								<th>Payment</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<img src="img/cutomer.png">
									<p>Sagar Navale</p>
								</td>
								<td>1100 RS</td>
								<td><span class="status completed">Completed</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/cutomer.png">
									<p>Vishal Navale</p>
								</td>
								<td>2000 RS</td>
								<td><span class="status completed">Completed</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/cutomer.png">
									<p>Bunny Vivek</p>
								</td>
								<td>250 RS</td>
								<td><span class="status process">Process</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/cutomer.png">
									<p>Prashant Pekhale</p>
								</td>
								<td>7880 RS</td>
								<td><span class="status pending">Pending</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/cutomer.png">
									<p>Rahul Navale</p>
								</td>
								<td>897 RS</td>
								<td><span class="status completed">Completed</span></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="order">
					<div class="head">
						<h3>Top Selling Products</h3>
						<!-- <i class='bx bx-search' ></i>
						<i class='bx bx-filter' ></i> -->
					</div>
					<table>
						<thead>
							<tr>
								<th>Products</th>
								<th>Avaiable Stock</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<img src="img/product.png">
									<p>Vivo T3 5G</p>
								</td>
								<td>55</td>
							</tr>
							<tr>
								<td>
									<img src="img/product.png">
									<p>Samsung A36</p>
								</td>
								<td>20</td>
							</tr>
							<tr>
								<td>
									<img src="img/product.png">
									<p>Redmi Note 11</p>
								</td>
								<td>45</td>
							</tr>
							<tr>
								<td>
									<img src="img/product.png">
									<p>I Phone 13</p>
								</td>
								<td>25</td>
							</tr>
							<tr>
								<td>
									<img src="img/product.png">
									<p>Vivo V50</p>
								</td>
								<td>27</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="todo">
					<div class="head">
						<h3>Low Stock Alerts</h3>
						<!-- <i class='bx bx-plus' ></i>
						<i class='bx bx-filter' ></i> -->
					</div>
					<ul class="todo-list">
						<li class="completed">
							<p>I Kall</p>
							<i class='bx bx bx-trash' ></i>
						</li>
						<li class="completed">
							<p>Intex</p>
							<i class='bx bx bx-trash' ></i>
						</li>
						<li class="not-completed">
							<p>Karbon</p>
							<i class='bx bx bx-trash' ></i>
						</li>
						<li class="completed">
							<p>MicroMax</p>
							<i class='bx bx bx-trash' ></i>
						</li>
						<li class="not-completed">
							<p>Max</p>
							<i class='bx bx bx-trash' ></i>
						</li>
					</ul>
				</div>
			</div>
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
	

	<script src="script.js"></script>
</body>
</html>