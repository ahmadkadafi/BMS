<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>BMS - @yield('title')</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="{{asset('templating/assets/img/kaiadmin/icon_kai.png')}}"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="{{asset('templating/assets/js/plugin/webfont/webfont.min.js')}}"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["{{asset('templating/assets/css/fonts.min.css')}}"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{asset('templating/assets/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" href="{{asset('templating/assets/css/plugins.min.css')}}" />
    <link rel="stylesheet" href="{{asset('templating/assets/css/kaiadmin.min.css')}}" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="{{asset('templating/assets/css/demo.css')}}" />
  </head>
  <body>
    <div class="wrapper">
      @include('partials.sidebar')

      <div class="main-panel">
        @include('partials.header')

        <div class="container">
          <div class="page-inner">
            @yield('content')
          </div>
        </div>
        @include('partials.footer')
      </div>
    </div>
    
    <!--   Core JS Files   -->
    <script src="{{asset('templating/assets/js/core/jquery-3.7.1.min.js')}}"></script>
    <script src="{{asset('templating/assets/js/core/popper.min.js')}}"></script>
    <script src="{{asset('templating/assets/js/core/bootstrap.min.js')}}"></script>

    <!-- jQuery Scrollbar -->
    <script src="{{asset('templating/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js')}}"></script>

    <!-- Chart JS -->
    <script src="{{asset('templating/assets/js/plugin/chart.js/chart.min.js')}}"></script>

    <!-- jQuery Sparkline -->
    <script src="{{asset('templating/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js')}}"></script>

    <!-- Chart Circle -->
    <script src="{{asset('templating/assets/js/plugin/chart-circle/circles.min.js')}}"></script>

    <!-- Datatables -->
    <script src="{{asset('templating/assets/js/plugin/datatables/datatables.min.js')}}"></script>

    <!-- Bootstrap Notify -->
    <script src="{{asset('templating/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js')}}"></script>

    <!-- jQuery Vector Maps -->
    <script src="{{asset('templating/assets/js/plugin/jsvectormap/jsvectormap.min.js')}}"></script>
    <script src="{{asset('templating/assets/js/plugin/jsvectormap/world.js')}}"></script>

    <!-- Google Maps Plugin -->
    <script src="{{asset('templating/assets/js/plugin/gmaps/gmaps.js')}}"></script>

    <!-- Sweet Alert -->
    <script src="{{asset('templating/assets/js/plugin/sweetalert/sweetalert.min.js')}}"></script>

    <!-- Kaiadmin JS -->
    <script src="{{asset('templating/assets/js/kaiadmin.min.js')}}"></script>
    
    @yield('adding')
  </body>
</html>
