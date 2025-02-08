$(document).ready(function(){
  var raw_log = $('#suricata_raw_log').html();
  var json_log = JSON.parse(raw_log);

  $('#suricata_formatted_log').html(JSON.stringify(json_log, undefined, 2));
  $('#suricata_formatted_log').hide();

  $('.suricata-siem-action').on('click', function(e){
    e.stopPropagation();
    e.preventDefault();

    $('#'+$(this).attr('data-selector')).show();
    $('#'+$(this).attr('data-selector')).siblings('pre').hide();

    $('.suricata-siem-action').removeClass('selected');
    $(this).addClass('selected');
  });

  if (!("payload" in json_log) || json_log.payload == ''){
    $('.class_siem_payload').hide();
  }
});
