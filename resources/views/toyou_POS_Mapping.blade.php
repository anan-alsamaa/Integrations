@include('includes.header')
@include('includes.nav')
<div class="container2">
    <div class="row text-center">
        <div class="col-xs-12">
            <img src="{{ asset('assets/img/toyou.png') }}" alt="ToYou Logo" class="top-image top-image2" id="logoImage">
        </div>
    </div>

    <div class="row text-center">
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="LCP-wrapper">
                <a href="#" class="open-modal" data-toggle="modal" data-target="#posMappingModal" data-action="{{ route('mapPOSLCP') }}" data-type="LCP">
                    <button>Start Mapping POS</button>
                </a>
                <img src="{{asset('assets/img/logos-LCP.png')}}" alt="LCP Logo" class="logo" id="LCP-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="psk-wrapper">
                <a href="#" class="open-modal" data-toggle="modal" data-target="#posMappingModal" data-action="{{ route('mapPOSPSK') }}" data-type="PSK">
                    <button>Start Mapping POS</button>
                </a>
                <img src="{{asset('assets/img/Poshak-logo.png')}}" alt="Poshak Logo" class="logo" id="Poshak-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="CND-wrapper">
                <a href="#" class="open-modal" data-toggle="modal" data-target="#posMappingModal" data-action="{{ route('mapPOSCND') }}" data-type="CND">
                    <button>Start Mapping POS</button>
                </a>
                <img src="{{asset('assets/img/CND-LOGO.png')}}" alt="CND Logo" class="logo" id="CND-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" id="Okashi-wrapper">
                <a href="#" class="open-modal" data-toggle="modal" data-target="#posMappingModal" data-action="{{ route('mapPOSOKS') }}" data-type="OKS">
                    <button>Start Mapping POS</button>
                </a>
                <img src="{{asset('assets/img/Okashi-LOGO.png')}}" alt="Okashi Logo" class="logo" id="Okashi-logo">
            </div>
        </div>
    </div>
</div>

<!-- POS Mapping Modal -->
<div id="posMappingModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">POS Mapping</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="posMappingForm">
                    <input type="hidden" id="pos-id" name="pos-id">
                    <div class="form-group">
                        <label for="pos-key">AFCO POS Key</label>
                        <input type="text" class="form-control" id="pos-key" name="pos-key" required>
                    </div>
                    <div class="form-group">
                        <label for="pos-location">Select POS Location</label>
                        <select class="form-control" id="pos-location" name="pos-location" required>
                            <option>Please select</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <input type="hidden" id="token" name="token" value="{{ session('authToken') }}">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="mapPOSButton">Map POS</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
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

        // Open modal and set action URL
        $('.open-modal').on('click', function() {
            var actionUrl = $(this).data('action');
            var posType = $(this).data('type'); // Get POS type from data-type attribute
            var form = $('#posMappingForm');
            form.attr('action', actionUrl);

            // Call the endpoint to fetch POS locations
            $.ajax({
                url: '/Integrations/public/fetchPOSLocations?posType=' + posType, // Use posType in the query
                method: 'GET',
                success: function(response) {
                    var posLocationSelect = $('#pos-location');
                    posLocationSelect.empty(); // Clear existing options

                    // Populate select with options
                    response.poses.forEach(function(pos) {
                        posLocationSelect.append(new Option(pos.name, pos.posId));
                    });

                    // Set up change event for location select
                    posLocationSelect.on('change', function() {
                        var selectedPosId = $(this).val();
                        $('#pos-id').val(selectedPosId); // Set the selected posId to the hidden field
                    });

                    // Open the modal after successfully loading the POS locations
                    $('#posMappingModal').modal('show');
                },
                error: function(xhr) {
                    alert('Failed to fetch POS locations: ' + xhr.status + ' ' + xhr.statusText);
                }
            });
        });

        // Submit form and show response
        $('#mapPOSButton').on('click', function() {
            var posId = $('#pos-id').val();
            if (!posId) {
                alert('Please select a POS location.');
                return;
            }

            $.ajax({
                url: $('#posMappingForm').attr('action'),
                method: 'POST',
                data: $('#posMappingForm').serialize(),
                success: function(response) {
                    $('#responseContent').text(JSON.stringify(response, null, 2));
                    $('#responseModal').modal('show');
                },
                error: function(xhr) {
                    $('#responseContent').text('Error: ' + xhr.status + ' ' + xhr.statusText);
                    $('#responseModal').modal('show');
                }
            });
        });
    });

</script>


@include('includes.footer')
