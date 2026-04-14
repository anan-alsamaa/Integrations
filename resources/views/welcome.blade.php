@include('includes.header')

<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
            <a href="{{ url('/toyou-choice') }}" >
                <div class="logo-wrapper" id="toyou-wrapper">
                    <img src="{{asset('assets/img/toyou.png')}}" alt="Toyou Logo" class="logo" id="toyou-logo">
                </div>
            </a>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
            <a href="{{url('/ninja-choice')}}" >
                <div class="logo-wrapper" id="ninja-wrapper">
                    <img src="{{asset('assets/img/ninja.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
                </div>
            </a>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
            <a href="{{url('/keta-choice')}}" >
                <div class="logo-wrapper" id="keta-wrapper">
                    <img src="{{asset('assets/img/keta.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
                </div>
            </a>
        </div>
    </div>
</div>

@include('includes.footer')
