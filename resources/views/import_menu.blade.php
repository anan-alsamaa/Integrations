<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa; /* Optional: Light background color for better visibility */
        }
        .container {
            width: 100%;
            max-width: 600px; /* Limit the form width */
        }
        .form-group {
            margin-bottom: 1.5em; /* Optional: Adjust spacing between form fields */
        }
        .btn-primary {
            width: 100%; /* Optional: Make the submit button full width */
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center">Sync Toyou Menu from POS</h1>
    <form id="transportationForm" method="POST" action="{{ route('importMenu') }}">
        @csrf
        <div class="form-group">
            <select id="transportType" name="transportType" class="form-control" required>
                <option value="" disabled selected>Select an option</option>
                <option value="lcp">LCP</option>
                <option value="cnd">CND</option>
                <option value="oks">OKS</option>
                <option value="psk">PSK</option>
            </select>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
            Submit
        </button>

    </form>
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

</body>
</html>

<script>
    $(document).ready(function() {
        $('#transportationForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting the traditional way

            var $submitButton = $(this).find('button[type="submit"]');
            var $spinner = $submitButton.find('.spinner-border');

            // Show spinner and disable the button
            $spinner.show();
            $submitButton.prop('disabled', true).text('Loading...');

            var formData = $(this).serialize(); // Serialize form data

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
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
                    $submitButton.prop('disabled', false).text('Submit');
                }
            });
        });
    });
</script>
