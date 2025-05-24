<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/{{ $staff_path }}">Trang chủ</a></li>
        <li class="nav-item"><a class="nav-link" href="/{{ $staff_path }}/config">Quản lý Config</a></li>
        <li class="nav-item"><a class="nav-link" href="/{{ $staff_path }}/user">Quản lý User</a></li>
        <li class="nav-item"><a class="nav-link" href="/{{ $staff_path }}/order">Quản lý Order</a></li>
      </ul>
    </div>
  </div>
</nav>
