<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
        <a href="/" class="logo">
            <img
            src="{{asset('templating/assets/img/kaiadmin/kai_logo.png')}}"
            alt="navbar brand"
            class="navbar-brand"
            height="23"
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
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
        <ul class="nav nav-secondary">
            <li class="nav-item">
                <a href="/">
                    <i class="fas fa-home"></i>
                    <p>Dashboard</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="/">
                    <i class="fas fa-brain"></i>
                    <p>AI Prediction</p>
                </a>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#monitoring">
                    <i class="fas fa-desktop"></i>
                    <p>Monitoring</p>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="monitoring">
                    <ul class="nav nav-collapse">
                        @foreach ($resors as $resor)
                            <li>
                                <a href="{{ url('/monitoring/resor/'.$resor->id) }}">
                                    <span class="sub-item">{{ $resor->nama }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#graphic">
                    <i class="fas fa-chart-bar"></i>
                    <p>Graphic</p>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="graphic">
                    <ul class="nav nav-collapse">
                        @foreach ($resors as $resor)
                            <li>
                                <a href="{{ url('/graphic/resor/'.$resor->id) }}">
                                    <span class="sub-item">{{ $resor->nama }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#line">
                    <i class="fas fa-chart-line"></i>
                    <p>Line</p>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="line">
                    <ul class="nav nav-collapse">
                        @foreach ($resors as $resor)
                            <li>
                                <a href="{{ url('/line/resor/'.$resor->id) }}">
                                    <span class="sub-item">{{ $resor->nama }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#table">
                    <i class="fas fa-table"></i>
                    <p>Tabel</p>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="table">
                    <ul class="nav nav-collapse">
                        @foreach ($resors as $resor)
                            <li>
                                <a href="{{ url('/table/resor/'.$resor->id) }}">
                                    <span class="sub-item">{{ $resor->nama }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            <li class="nav-item">
            <a data-bs-toggle="collapse" href="#report">
                <i class="fas fa-file"></i>
                <p>Report</p>
                <span class="caret"></span>
            </a>
            <div class="collapse" id="report">
                <ul class="nav nav-collapse">
                    @foreach ($resors as $resor)
                        <li>
                            <a href="{{ url('/report/resor/'.$resor->id) }}">
                                <span class="sub-item">{{ $resor->nama }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            </li>
            <li class="nav-item">
            <a data-bs-toggle="collapse" href="#logger">
                <i class="fas fa-table"></i>
                <p>Logger</p>
                <span class="caret"></span>
            </a>
            <div class="collapse" id="logger">
                <ul class="nav nav-collapse">
                    @foreach ($resors as $resor)
                        <li>
                            <a href="{{ url('/logger/resor/'.$resor->id) }}">
                                <span class="sub-item">{{ $resor->nama }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            </li>
            <li class="nav-item">
            <a data-bs-toggle="collapse" href="#batterysetting">
                <i class="fas fa-cogs"></i>
                <p>Battery Setting</p>
                <span class="caret"></span>
            </a>
            <div class="collapse" id="batterysetting">
                <ul class="nav nav-collapse">
                    @foreach ($resors as $resor)
                        <li>
                            <a href="{{ url('/batterysetting/resor/'.$resor->id) }}">
                                <span class="sub-item">{{ $resor->nama }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            </li>
            <li class="nav-item">
            <a href="/about">
                <i class="fas fa-info-circle"></i>
                <p>About</p>
            </a>
            </li>
            <li class="nav-item">
            <a href="/test">
                <i class="fas fa-info-circle"></i>
                <p>Test</p>
            </a>
            </li>
        </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->