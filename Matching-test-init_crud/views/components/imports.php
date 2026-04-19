<?php

function imports($path = "")
{
    $assetBase = "/pleaseeee/Matching-test-init_crud/assets";
    return " 
    <!-- Bootstrap core JavaScript-->
    <script src='$assetBase/vendor/jquery/jquery.min.js'></script>
    <script src='$assetBase/vendor/bootstrap/js/bootstrap.bundle.min.js'></script>

    <!-- Core plugin JavaScript-->
    <script src='$assetBase/vendor/jquery-easing/jquery.easing.min.js'></script>

    <!-- Custom scripts for all pages-->
    <script src='$assetBase/js/sb-admin-2.min.js'></script>

    <!-- Page level plugins -->
    <script src='$assetBase/vendor/chart.js/Chart.min.js'></script>

    <!-- Page level custom scripts -->
    <script src='$assetBase/js/demo/chart-area-demo.js'></script>
    <script src='$assetBase/js/demo/chart-pie-demo.js'></script>
    ";
};
