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
            <div class="logo-wrapper" data-brand-id="1" id="LCP-wrapper">
                <a href="#" data-toggle="modal" data-target="#addBranchModal">
                    <button class="sync-brand-button">Add Branch</button>
                </a>
                <img src="{{ asset('assets/img/logos-LCP.png') }}" alt="LCP Logo" class="logo" id="LCP-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="1004" id="psk-wrapper">
                <a href="#" data-toggle="modal" data-target="#addBranchModal">
                    <button class="sync-brand-button">Add Branch</button>
                </a>
                <img src="{{ asset('assets/img/Poshak-logo.png') }}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="2" id="CND-wrapper">
                <a href="#" data-toggle="modal" data-target="#addBranchModal">
                    <button class="sync-brand-button">Add Branch</button>
                </a>
                <img src="{{ asset('assets/img/CND-LOGO.png') }}" alt="Ninja Logo" class="logo" id="ninja-logo">
            </div>
        </div>
        <div class="col-xs-12 col-sm-3 d-flex justify-content-center align-items-center">
            <div class="logo-wrapper" data-brand-id="3" id="Okashi-wrapper">
                <a href="#" data-toggle="modal" data-target="#addBranchModal">
                    <button class="sync-brand-button">Add Branch</button>
                </a>
                <img src="{{ asset('assets/img/Okashi-LOGO.png') }}" alt="Okashi Logo" class="logo" id="okashi-logo">
            </div>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1" role="dialog" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBranchModalLabel">Add Branch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addBranchForm">
                    <div class="form-group">
                        <label for="posId">POS ID</label>
                        <input type="text" class="form-control" id="posId" placeholder="Enter POS ID" required>
                    </div>
                    <div class="form-group">
                        <label for="keetaId">Keeta ID</label>
                        <input type="text" class="form-control" id="keetaId" placeholder="Enter Keeta ID" required>
                    </div>
                    <div class="form-group">
                        <label for="branchName">Branch Name</label>
                        <input type="text" class="form-control" id="branchName" placeholder="Enter Branch Name" required>
                    </div>
                    <div class="form-group">
                        <label for="pos_system">Pos System</label>
                        <select class="form-control" name="pos_system" id="pos_system">
                            <option value="">SDM (default)</option>
                            <option value = "sara">SARA</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        Submit
                        <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </form>
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
        let selectedBrandId = 1; // Default brand ID
        let selectedconceptID = 1; // Default conceptID
        let selectedmenuTemplateID = 1; // Default TemplateID

        $('.sync-brand-button').on('click', function() {
            selectedBrandId = $(this).closest('.logo-wrapper').data('brand-id');
            selectedconceptID = $(this).closest('.logo-wrapper').data('concept-id'); // Corrected
            selectedmenuTemplateID = $(this).closest('.logo-wrapper').data('menu-template-id'); // Corrected
        });

        $('#addBranchForm').on('submit', function(e) {
            e.preventDefault();

            var posId = $('#posId').val();
            var branchName = $('#branchName').val();
            var keetaId = $('#keetaId').val(); // Get the Keeta ID value
            var posSystem = $('#pos_system').val();
            if(posSystem === '') {
                posSystem = 'sdm';
            }

            $('#submitButton').prop('disabled', true);
            $('#spinner').show();

            $.ajax({
                url: '{{ route("keeta-branches.store") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    pos_key: posId,
                    branch_name: branchName,
                    brand_id: selectedBrandId,
                    keeta_id: keetaId, // Include Keeta ID in the request
                    pos_system: posSystem,
                    conceptID: selectedconceptID,
                    menuTemplateID: selectedmenuTemplateID
                },
                success: function(response) {
                    $('#submitButton').prop('disabled', false);
                    $('#spinner').hide();

                    // Display a success message and reset the form
                    $('#addBranchForm').prepend('<div class="alert alert-success">Branch added successfully!</div>');

                    // Clear form fields after a delay
                    setTimeout(function() {
                        $('.alert-success').fadeOut('slow', function() {
                            $(this).remove(); // Remove the success message
                            $('#posId').val(''); // Clear POS ID field
                            $('#branchName').val(''); // Clear Branch Name field
                            $('#keetaId').val(''); // Clear Keeta ID field
                        });
                    }, 2000); // Keep the success message for 2 seconds
                },
                error: function(xhr, status, error) {
                    $('#submitButton').prop('disabled', false);
                    $('#spinner').hide();

                    // Display an error message
                    $('#addBranchForm').prepend('<div class="alert alert-danger">Error: ' + xhr.responseText + '</div>');

                    // Automatically remove the error message after a few seconds
                    setTimeout(function() {
                        $('.alert-danger').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 5000); // Message will fade out after 5 seconds
                }
            });
        });

    });
</script>
@include('includes.footer')
