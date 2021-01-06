// called once jquery is ready
$(function() {
    $('#statistics_period_start').datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        showWeek: true,
        firstDay: 1
    });
    $('#statistics_period_end').datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        showWeek: true,
        firstDay: 1
    });

    // bind to click handler for submission
    $(document)
        .on('click', '#statistics_send', function() {
            $('html').addClass('waiting');
            var period_start = $('#statistics_period_start').val();
            var period_end = $('#statistics_period_end').val();
            var updated = $('#statistics_updated').val();
            var type = $('#statistics_type').val();
            var interval = $('#statistics_interval').val();

            $.ajax({
                url: "jacqStatistics_ptlp.php?id=statistics",
                data: {
                    periodStart: period_start,
                    periodEnd: period_end,
                    updated: updated,
                    type: type,
                    interval: interval
                },
                dataType: "json",
                success: function(data) {
                    $('html').removeClass('waiting');
                    $('#statistics_result').html(data.display);
                    plotData = data.plot;
        //            alert(data.plot[0].data.toSource());
                    if (data.plotMaxIndex > 2) {
                        plotInstitution(data.plotMaxIndex - 1);
                    } else if (data.plotMaxIndex > 0) {
                        plotInstitution(0);
                    } else {
                        $('#statistics_plot').empty();
                    }
                }
            });
        });
});