jQuery(document).ready(function ($) {
    $('.third-party-action-button').on('click', function (e) {
        
        e.preventDefault(); 
        var orderId = $(this).data('order-id');
        $.ajax({
            url: thirdPartyAction.ajax_url,
            method: 'POST',
            data: {
                action: 'third_party_order_action',
                order_id: orderId,
                nonce: thirdPartyAction.nonce
            },
            success: function (response) {
                if (response.success) {
                    // setOrderProgress("Shipped");
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });
});

function setOrderProgress(status) {
    const steps = ["ordered", "processed", "shipped", "delivered"];
    let currentStep = steps.indexOf(status.toLowerCase());

    steps.forEach((step, index) => {
        let stepElement = document.getElementById(`step-${step}`);
        if (index < currentStep) {
            stepElement.classList.add("completed");
        } else if (index === currentStep) {
            stepElement.classList.add("active");
        }
    });
}

// Example usage
setOrderProgress("Shipped");