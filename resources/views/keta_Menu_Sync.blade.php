@include('includes.header')
@include('includes.nav')

<div class="container2">
    <div class="row text-center">
        <div class="col-xs-12">
            <img src="{{ asset('assets/img/keta.png') }}" alt="ToYou Logo" class="top-image top-image2" id="logoImage">
        </div>
    </div>

    <div class="row text-center">
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="1" id="toyou-wrapper">
                <a href="#">
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/logos-LCP.png')}}" alt="Toyou Logo" class="logo" id="toyou-logo">
            </div>
            <a href="#" data-toggle="modal" data-target="#syncBranchModal" data-brand-id="1" id="branch-toyou-wrapper" class="sync-branch-button" style="margin-top: 10px; display: inline-block;">
                <button>Sync Branch</button>
            </a>
            <a href="#" class="sync-sara-button" data-brand-id="1" style="margin-top: 10px; display: inline-block;">
                <button>Sync SARA</button>
            </a>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="1004" id="psk-wrapper">
                <a href="#">
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/Poshak-logo.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
            <a href="#" data-toggle="modal" data-target="#syncBranchModal" data-brand-id="1004" id="branch-psk-wrapper" class="sync-branch-button" style="margin-top: 10px; display: inline-block;">
                <button>Sync Branch</button>
            </a>
            <a href="#" class="sync-sara-button" data-brand-reference-id="1004" style="margin-top: 10px; display: inline-block;">
                <button>Sync SARA</button>
            </a>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="2" id="CND-wrapper">
                <a href="#">
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/CND-LOGO.png')}}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
            <a href="#" data-toggle="modal" data-target="#syncBranchModal" data-brand-id="2" id="branch-CND-wrapper" class="sync-branch-button" style="margin-top: 10px; display: inline-block;">
                <button>Sync Branch</button>
            </a>
            <a href="#" class="sync-sara-button" data-brand-id="2" style="margin-top: 10px; display: inline-block;">
                <button>Sync SARA</button>
            </a>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="3" id="Okashi-wrapper">
                <a href="#">
                    <button>Sync Brand</button>
                </a>
                <img src="{{asset('assets/img/Okashi-LOGO.png')}}" alt="Okashi Logo" class="logo" id="okashi-logo">
            </div>
            <a href="#" data-toggle="modal" data-target="#syncBranchModal" data-brand-id="3" id="branch-Okashi-wrapper" class="sync-branch-button" style="margin-top: 10px; display: inline-block;">
                <button>Sync Branch</button>
            </a>
            <a href="#" class="sync-sara-button" data-brand-id="3" style="margin-top: 10px; display: inline-block;">
                <button>Sync SARA</button>
            </a>
        </div>
    </div>
</div>

<!-- modal for restaurant pos_id -->
<div class="modal fade" id="syncBranchModal" tabindex="-1" role="dialog" aria-labelledby="syncBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="syncBranchModalLabel">Sync Branch Menu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="syncBranchForm">
                    <div class="form-group">
                        <label for="syncPosKey">POS Key</label>
                        <input type="text" class="form-control" id="syncPosKey" placeholder="Enter POS Key" required>
                    </div>

                    <button type="submit" class="btn btn-primary" id="syncBranchSubmit">
                        Sync Menu
                        <span id="syncSpinner" class="spinner-border spinner-border-sm" style="display:none"></span>
                    </button>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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


        let brandId = null;
        $('.sync-branch-button').on('click', function () {
            brandId = $(this).data('brand-id');
        });

        $('#syncBranchForm').on('submit', function (e) {
        e.preventDefault();

        const posKey = $('#syncPosKey').val();

        if (!brandId) {
            alert('Brand not selected');
            return;
        }

        if (!posKey) {
            alert('Please enter POS key');
            return;
        }

        $('#syncBranchSubmit').prop('disabled', true);
        $('#syncSpinner').show();

        $.ajax({
            url: '{{ route("sync.menu-branch-keta") }}',
            type: 'POST',
            data: {
                brand_id: brandId,
                pos_key: posKey
            },
            success: function (response) {
            $('#responseContent').text(JSON.stringify(response, null, 2));

            // Show response modal ON TOP
            $('#responseModal').modal({
                backdrop: 'static',
                keyboard: false
            });

            $('#responseModal').modal('show');

            $('#syncBranchSubmit').prop('disabled', false);
            $('#syncSpinner').hide();
        },
            error: function (xhr) {
            $('#responseContent').text('Error: ' + xhr.status + '\n' + xhr.responseText);
            $('#responseModal').modal('show');

            $('#syncBranchSubmit').prop('disabled', false);
            $('#syncSpinner').hide();

            $('#syncPosKey').val('');
        }
        });

    });


        $('.logo-wrapper a').on('click', function(event) {
            event.preventDefault(); // Prevent the default anchor behavior

            var $submitButton = $(this).find('button');
            var $spinner = $submitButton.find('.spinner-border');
            var brandId = $(this).closest('.logo-wrapper').data('brand-id');

            // Show spinner and disable the button
            $submitButton.prop('disabled', true).text('Loading...');

            $.ajax({
                url: '{{ route("sync.menu-keta") }}',
                method: 'POST',
                data: { brand_id: brandId },
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
                    $submitButton.prop('disabled', false).text('Sync Brand');
                }
            });
        });

        // Sync SARA Only click handler
        $('.sync-sara-button').on('click', function(event) {
            event.preventDefault();

            var $submitButton = $(this).find('button');
            var brandReferenceId = $(this).data('brand-reference-id');

            // Show loading text and disable the button
            $submitButton.prop('disabled', true).text('Loading...');

            $.ajax({
                url: '{{ route("sync.menu-sara-keta") }}',
                method: 'POST',
                data: { brand_reference_id: brandReferenceId },
                success: function(response) {
                    $('#responseContent').text(JSON.stringify(response, null, 2));
                    $('#responseModal').modal('show');
                },
                error: function(xhr) {
                    $('#responseContent').text('Error: ' + xhr.status + ' ' + xhr.statusText + '\n' + xhr.responseText);
                    $('#responseModal').modal('show');
                },
                complete: function() {
                    // Reset button string after response
                    $submitButton.prop('disabled', false).text('Sync SARA');
                }
            });
        });

    });
</script>

@include('includes.footer')
