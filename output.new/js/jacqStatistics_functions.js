/* global plotData */

/**
 * plot data of given institution
 * @param {integer} institution index to institution to plot
 */
function plotInstitution(institution)
{
    $.plot('#statistics_plot', [{
            label: plotData[institution].label,
            data: plotData[institution].data
        }], {
            series: {
                bars: {
                    show: true,
                    barWidth: 0.6,
                    align: "center"
                }
            }
        }
    );
}