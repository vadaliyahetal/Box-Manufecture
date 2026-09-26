$(document).ready(function () {
    function calculatePrice() {
        let length = parseFloat($('#length').val()) || 0;
        let width = parseFloat($('#width').val()) || 0;
        let height = parseFloat($('#height').val()) || 0;
        let unit = $('#unit').val();
        let material = $('#material').val();
        let quality = $('#quality').val();
        let quantity = parseInt($('#quantity').val()) || 1;
        let printColor = $('#print_color').val();

        // Convert to CM (base unit)
        let l_cm = length, w_cm = width, h_cm = height;
        if (unit === 'inch') {
            l_cm = length * 2.54;
            w_cm = width * 2.54;
            h_cm = height * 2.54;
        } else if (unit === 'feet') {
            l_cm = length * 30.48;
            w_cm = width * 30.48;
            h_cm = height * 30.48;
        }

        // Calculate surface area (approximation for cost)
        let area = 2 * ((l_cm * w_cm) + (l_cm * h_cm) + (w_cm * h_cm));

        // Base rate per sq cm
        let baseRate = 0.1;

        // Material multiplier
        let materialMultiplier = 1;
        if (material === 'corrugated') materialMultiplier = 1.2;
        else if (material === 'rigid') materialMultiplier = 1.5;

        // Quality multiplier
        let qualityMultiplier = 1;
        if (quality === 'double') qualityMultiplier = 1.3;
        else if (quality === 'triple') qualityMultiplier = 1.5;

        // Calculate base price per box
        let pricePerBox = area * baseRate * materialMultiplier * qualityMultiplier;

        // Add design and logo charges if uploaded
        let designPrice = $('#design_file')[0].files.length > 0 ? 100 : 0;
        let logoPrice = $('#logo_file')[0].files.length > 0 ? 50 : 0;
        let colorPrice = (printColor && printColor !== 'none') ? 30 : 0;

        // Total before GST (per box + extras) * quantity
        let subtotal = (pricePerBox * quantity) + designPrice + logoPrice + colorPrice;

        // Calculate GST (18%)
        let gst = subtotal * 0.18;

        // Final total with GST
        let totalPrice = subtotal + gst;

        // Display breakdown
        $('#price_display').html(
            '<div class="alert alert-info">' +
            '<h5>Price Breakdown:</h5>' +
            '<table class="table table-sm mb-0">' +
            '<tr><td>Quantity:</td><td>' + quantity + ' boxes</td></tr>' +
            '<tr><td>Price per box:</td><td>₹' + pricePerBox.toFixed(2) + '</td></tr>' +
            '<tr><td>Box cost:</td><td>₹' + (pricePerBox * quantity).toFixed(2) + '</td></tr>' +
            (designPrice > 0 ? '<tr><td>Design upload:</td><td>₹' + designPrice.toFixed(2) + '</td></tr>' : '') +
            (logoPrice > 0 ? '<tr><td>Logo upload:</td><td>₹' + logoPrice.toFixed(2) + '</td></tr>' : '') +
            (colorPrice > 0 ? '<tr><td>Color printing:</td><td>₹' + colorPrice.toFixed(2) + '</td></tr>' : '') +
            '<tr><td><strong>Subtotal:</strong></td><td><strong>₹' + subtotal.toFixed(2) + '</strong></td></tr>' +
            '<tr><td>GST (18%):</td><td>₹' + gst.toFixed(2) + '</td></tr>' +
            '<tr class="table-primary"><td><strong>Total Amount:</strong></td><td><strong>₹' + totalPrice.toFixed(2) + '</strong></td></tr>' +
            '</table>' +
            '</div>'
        );
    }

    // Trigger calculation on input change
    $('#length, #width, #height, #unit, #material, #quality, #quantity, #print_color').on('input change', calculatePrice);
    $('#design_file, #logo_file').on('change', calculatePrice);

    // Initial calculation
    calculatePrice();
});
