<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>Document</title>
</head>
<body style="background-color: rgba(216, 216, 216, 1);">


<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 400px;">
        <img src="assets/images/aarc-360-logo-1.webp" alt="" class="mx-auto d-block" style="width: 50%;">
        <div class="mt-4"></div>
      <h5 class="text-center mb-2">Welcome Back</h3>
      <p class="text-center text-muted">Sign in to access your engagement schedules</p>
      <form class="p-4">
        <div class="mb-3">
          <label for="email" class="form-label">Email address</label>
          <input type="email" class="form-control" id="email" placeholder="Enter email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" placeholder="Password" required>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn" style="background-color: rgb(23,62, 70); color: white;">Login</button>
        </div>
      </form>
      <p class="text-center text-muted">
        Contact your administrator for account setup.
        <br>
        <span style="font-size: 10px;">
            This is a demo application. In production, users would be created by administrators.
        </span>
      </p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    


</body>
</html>