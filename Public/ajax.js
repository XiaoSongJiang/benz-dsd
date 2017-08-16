
$(function(){
  $(".btn-search").click(function() {
    $('form').submit();
  });

  function updateProvince() {
    var region_code = $("#region").val();
    var url = "<?php echo U('Admin/Common/ajaxProvince'); ?>";
    var param = {
      region_code: region_code
    };
    $.ajax({
      url: url,
      data: param,
    })
    .done(function(data) {
      $("#province").html(data);
    });
  }
  function updateCity() {
    var url = "<?php echo U('Admin/Common/ajaxCity'); ?>";
    var param = {
      province: $("#province").val(),
      district_id: $("#DM").val()
    
    $.ajax({
      url: url,
      data: param,
    })
    .done(function(data) {
      $("#city").html(data);
    });
  }
  function updateDealer() {
    var url = "<?php echo U('Admin/Common/ajaxDealer'); ?>";
    var param = {
      region_code: $("#region").val(),
      province_code: $("#province").val(),
      city_code: $("#city").val(),
      district_id: $("#DM").val(),
      group: $("#group").val(),
    }
    $.ajax({
      url: url,
      data: param,
    })
    .done(function(data) {
      $("#dealer").html(data);
    });
  }
  function updateDistrict() {
    var url = "<?php echo U('Admin/Common/ajaxDistrict'); ?>";
    var param = {
      region_code: $("#region").val()
    }
    $.ajax({
      url: url,
      data: param,
    })
    .done(function(data) {
      $("#DM").html(data);
    });
  }
  $("#region").change(function() {
    updateProvince();
    updateCity();
    updateDistrict();
    updateDealer();
  });
  $("#province").change(function(){
    updateCity();
    updateDealer();
  });
  $("#city").change(function() {
    updateDealer();
  });
  $("#DM").change(function() {
    updateDealer();
  });
  $("#group").change(function() {
    updateDealer();
  });
});