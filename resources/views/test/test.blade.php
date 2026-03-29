<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<button id="recalc_all_stocks_btn" 
        class="btn btn-danger btn-sm">
    <i class="fa fa-refresh"></i> Recalculate All Stocks
</button>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $('#recalc_all_stocks_btn').click(function() {

    if(!confirm("Are you sure? This will recalculate stock for ALL products!")) {
        return;
    }

    $.ajax({
        url: "{{ route('products.recalc_all_stocks') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}"
        },
        beforeSend: function() {
            $('#recalc_all_stocks_btn').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Recalculating...');
        },
        success: function(result) {
            alert(result.message);
        },
        error: function(xhr) {
            alert("Error: Unable to recalculate.");
        },
        complete: function() {
            $('#recalc_all_stocks_btn').prop('disabled', false)
                .html('<i class="fa fa-refresh"></i> Recalculate All Stocks');
        }
    });

});
</script>
</body>
</html>