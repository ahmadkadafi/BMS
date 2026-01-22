<div class="main-header">
<div class="main-header-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
    <a href="index.html" class="logo">
        <img
        src="assets/img/kaiadmin/logo_light.svg"
        alt="navbar brand"
        class="navbar-brand"
        height="20"
        />
    </a>
    <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
        <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
        <i class="gg-menu-left"></i>
        </button>
    </div>
    <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
    </button>
    </div>
    <!-- End Logo Header -->
</div>
<!-- Navbar Header -->
<nav
    class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
>
    <div class="container-fluid">
    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
        <li
        class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none"
        >
        <a
            class="nav-link dropdown-toggle"
            data-bs-toggle="dropdown"
            href="#"
            role="button"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <i class="fa fa-search"></i>
        </a>
        <ul class="dropdown-menu dropdown-search animated fadeIn">
            <form class="navbar-left navbar-form nav-search">
            <div class="input-group">
                <input
                type="text"
                placeholder="Search ..."
                class="form-control"
                />
            </div>
            </form>
        </ul>
        </li>
        <li class="nav-item topbar-icon dropdown hidden-caret">
        <a
            class="nav-link dropdown-toggle"
            href="#"
            id="messageDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
        >
            <i class="fa fa-envelope"></i>
        </a>
        <ul
            class="dropdown-menu messages-notif-box animated fadeIn"
            aria-labelledby="messageDropdown"
        >
            <li>
            <div
                class="dropdown-title d-flex justify-content-between align-items-center"
            >
                Messages
                <a href="#" class="small">Mark all as read</a>
            </div>
            </li>
            <li>
            <a class="see-all" href="javascript:void(0);"
                >See all messages<i class="fa fa-angle-right"></i>
            </a>
            </li>
        </ul>
        </li>
        <li class="nav-item topbar-icon dropdown hidden-caret">
        <a
            class="nav-link dropdown-toggle"
            href="#"
            id="notifDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
        >
            <i class="fa fa-bell"></i>
            <span class="notification">1</span>
        </a>
        <ul
            class="dropdown-menu notif-box animated fadeIn"
            aria-labelledby="notifDropdown"
        >
            <li>
            <div class="dropdown-title">
                You have 1 new notification
            </div>
            </li>
            <li>
            <a class="see-all" href="javascript:void(0);"
                >See all notifications<i class="fa fa-angle-right"></i>
            </a>
            </li>
        </ul>
        </li>

        <li class="nav-item topbar-user dropdown hidden-caret">
        <a
            class="dropdown-toggle profile-pic"
            data-bs-toggle="dropdown"
            href="#"
            aria-expanded="false"
        >
            <div class="avatar-sm">
            <img
                src="{{asset('templating/assets/img/profile.jpg')}}"
                alt="..."
                class="avatar-img rounded-circle"
            />
            </div>
            <span class="profile-username">
            <span class="op-7">Hi,</span>
            <span class="fw-bold">Ahmad Kadafi</span>
            </span>
        </a>
        <ul class="dropdown-menu dropdown-user animated fadeIn">
            <div class="dropdown-user-scroll scrollbar-outer">
            <li>
                <div class="user-box">
                <div class="avatar-lg">
                    <img
                    src="{{asset('templating/assets/img/profile.jpg')}}"
                    alt="image profile"
                    class="avatar-img rounded"
                    />
                </div>
                <div class="u-text">
                    <h4>Ahmad Kadafi</h4>
                    <p class="text-muted">akadafi12@gmail.com</p>
                    <a
                    href="profile.html"
                    class="btn btn-xs btn-secondary btn-sm"
                    >View Profile</a
                    >
                </div>
                </div>
            </li>
            <li>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#">Account Setting</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#">Logout</a>
            </li>
            </div>
        </ul>
        </li>
    </ul>
    </div>
</nav>
<!-- End Navbar -->
</div>