<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Router Task Runner</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

		<!-- Bootstrap core JavaScript -->
	    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
	    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
	    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>

		<link rel="stylesheet" href="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.0.0/build/styles/monokai.min.css">
		<script src="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.0.0/build/highlight.min.js"></script>

		<link href="<?=getBasePath()?>style.css" rel="stylesheet">
	</head>

	<body>
		<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
			<a class="navbar-brand" href="<?=getBasePath()?>">Router Task Runner</a>
			<ul class="navbar-nav mr-auto">
				<li class="nav-item"><a class="nav-link" href="<?=getBasePath()?>scheduled/">Scheduled tasks</a></li>
				<?php if (isLoggedInAdmin()) { ?>
					<li class="nav-item"><a class="nav-link" href="<?=getBasePath()?>listAll.php">All tasks</a></li>
				<?php } ?>
			</ul>
			<ul class="navbar-nav">
				<?php if (isLoggedInAdmin()) { ?>
					<li class="nav-item"><a class="nav-link" href="<?=getBasePath()?>logout.php">Logout</a></li>
				<?php } else { ?>
					<li class="nav-item"><a class="nav-link" href="<?=getBasePath()?>login.php">Login</a></li>
				<?php } ?>
			</ul>
    	</nav>

		<div class="container">
