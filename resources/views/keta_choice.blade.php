@include('includes.header')
@include('includes.nav')
<div class="container2">
    <div class="row text-center">
        <!-- Image at the top, centered -->
        <div class="col-xs-12">
            <img src="{{ asset('assets/img/keta.png') }}" alt="ToYou Logo" class="top-image" id="logoImage">
        </div>
    </div>

    <div class="row text-center">
        <div class="col-xs-12 col-sm-6 d-flex justify-content-center align-items-center">
            <a style="color: #4d555e87" href="{{ url('/keta-menu-sync') }}">
                <div class="logo-wrapper" id="toyou-wrapper">
                    <div class="logo">
                        <h1>Menu Sync</h1>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xs-12 col-sm-6 d-flex justify-content-center align-items-center">
            <a style="color: #4d555e87" href="{{ url('/keeta-add-branch') }}">
                <div class="logo-wrapper" id="ninja-wrapper">
                    <div class="logo">
                        <h1>Add Branch</h1>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

@include('includes.footer')

