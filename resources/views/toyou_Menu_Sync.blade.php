@include('includes.header')
@include('includes.nav')
<div class="container2">
    <div class="row text-center">
        <!-- Image at the top, centered -->
        <div class="col-xs-12 pt-sm-20">
            <img src="{{ asset('assets/img/toyou.png') }}" alt="ToYou Logo" class="top-image top-image2" id="logoImage">
        </div>
    </div>

    <div class="row text-center">
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="LCP-wrapper">
                <a href="{{ route('importMenu-toyou-lcp') }}" >
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/logos-LCP.png')}}" alt="Toyou Logo" class="logo" id="toyou-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="psk-wrapper">
                <a href="{{ route('importMenu-toyou-psk') }}" >
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/Poshak-logo.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="CND-wrapper">
                <a href="{{ route('importMenu-toyou-cnd') }}" >
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/CND-LOGO.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="Okashi-wrapper">
                <a href="{{ route('importMenu-toyou-okashi') }}" >
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/Okashi-LOGO.png')}}" alt="Okashi Logo" class="logo" id="okashi-logo">
            </div>
        </div>

    </div>
</div>

<!-- Modal -->
<div id="responseModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Response</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="responseContent"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.logo-wrapper a').on('click', function(event) {
            event.preventDefault(); // Prevent the default anchor behavior

            var $submitButton = $(this).find('button');
            var $spinner = $submitButton.find('.spinner-border');

            // Show spinner and disable the button
            $spinner.show();
            $submitButton.prop('disabled', true).text('Loading...');

            $.ajax({
                url: $(this).attr('href'),
                method: 'POST',
                data: {
                    transportType: $submitButton.closest('.logo-wrapper').attr('id') // Only send transportType
                },
                success: function(response) {
                    $('#responseContent').text(JSON.stringify(response, null, 2));
                    $('#responseModal').modal('show');
                },
                error: function(xhr) {
                    $('#responseContent').text('Error: ' + xhr.status + ' ' + xhr.statusText);
                    $('#responseModal').modal('show');
                },
                complete: function() {
                    // Hide spinner and enable the button after the response
                    $spinner.hide();
                    $submitButton.prop('disabled', false).text('Sync Brand');
                }
            });
        });
    });


</script>

@include('includes.footer')
