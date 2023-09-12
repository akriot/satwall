<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

include 'phpqrcode/qrlib.php';

// gmail credentials
putenv("PASSWORD=dduxjcqbagibfjby");
putenv("GMAIL=airporttaxi.rides@gmail.com");
putenv("GOOGLEMAPAPIKEY=AIzaSyC0BPzIIlQUNJgvge9EpSstUS4MbKPPSLo");
putenv("LNPAYSECRETKEY=sak_I3VWHU8nZ8w2Odm7DNC1CIxxwWnLq3o");

if (isset($_POST['FormSubmit'])) {
    $apiKey = getenv('GOOGLEMAPAPIKEY'); 
    $startPoint = $_POST['pickupLocation'];
    $homeBaseZipCode = $_POST['homeBaseZipCode'];
    $endPoint = $_POST['dropLocation'];
    $desiredDestinationZipCode = $_POST['desiredDestinationZipCode'];
    
    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=' . str_replace(' ', '+', $startPoint) . '&destinations=' . str_replace(' ', '+', $endPoint) . '&key=' . $apiKey;

    $predeadurl = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=' . str_replace(' ', '+', $startPoint) . '&destinations=' . str_replace(' ', '+', $homeBaseZipCode) . '&key=' . $apiKey;

    $postdeadurl = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=' . str_replace(' ', '+', $desiredDestinationZipCode) . '&destinations=' . str_replace(' ', '+', $endPoint) . '&key=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $response_all = json_decode($response);

    $ch1 = curl_init();
    curl_setopt($ch1, CURLOPT_URL, $predeadurl);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch1, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, 0);
    $predeadresponse = curl_exec($ch1);
    curl_close($ch1);
    $predeadresponse_all = json_decode($predeadresponse);

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $postdeadurl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
    $postdeadresponse = curl_exec($ch2);
    curl_close($ch2);
    $postdeadresponse_all = json_decode($postdeadresponse);


    $tip = $_POST['tip'];
    function cal_estimated_cost($totaTime, $totalMiles, $vType, $isReturnEstd){
        $estimate = 0;
        if ($vType == 'a') {
            $minAmount = 10.00;
            $estimate = ceil((0.30 * $totaTime) + (2 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount;
            }
        } else if ($vType == 'XL') {
            $minAmount = 12.00;
            $estimate = ceil((0.30 * $totaTime) + (2.50 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount + ($minAmount * 0.05);
            }
        } else if ($vType == 'new') {
            $minAmount = 15.00;
            $estimate = ceil((0.30 * $totaTime) + (3 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount;
            }
        } else if ($vType == 'new XL') {
            $minAmount = 18.00;
            $estimate = ceil((0.30 * $totaTime) + (3.50 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount;
            }
        } else if ($vType == 'Limo') {
            $minAmount = 100.00;
            $estimate = ceil((0.50 * $totaTime) + (5 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount;
            }
        } else if ($vType == 'Tow Truck') {
            $minAmount = 50.00;
            $estimate = ceil((0.50 * $totaTime) + (5 * $totalMiles));
            if ($estimate < $minAmount) {
                $estimate = $minAmount;
            }
        }else if($vType == 'pre dead'){
            return ceil((0.25 * $totaTime) + (0.60 * $totalMiles));
        }else if($vType == 'post dead'){
            return ceil((0.25 * $totaTime) + (0.60 * $totalMiles));
        }
        if($isReturnEstd == true){
            return $estimate;
        }
        $tipAmount = ceil(($estimate * $_POST['tip']) / 100);
        return ceil($estimate + $tipAmount);
    }

    function uberLyftPay($totaTime, $totalMiles, $vType, $isReturnEstd){
        
        $minUberLyftNetPay = 2.00;
        $uberLyftEstimatePay = ceil((0.05 * $totaTime) + (0.30 * $totalMiles));
        if($minUberLyftNetPay < $uberLyftEstimatePay){
            $uberLyftNetPay = $uberLyftEstimatePay;
        }else{
            $uberLyftNetPay = $minUberLyftNetPay;
        }
        return $uberLyftNetPay;
    }

    function satoshiQR($amount){
        $url = "https://api.coinconvert.net/convert/usd/btc?amount=".floatval($amount);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($resp);
        $btc_amount = $result->BTC;
        $satoshi_per_btc = 100000000;
        $net_satoshi = ($btc_amount * $satoshi_per_btc);
        if($net_satoshi > 250000){
            return "";
        }
        //LNPAY
        $request_array = array(
            "num_satoshis"=> $net_satoshi,
            "memo"=> "Online satoshi transfar"
        );
        $request_json = json_encode($request_array);
        $secret = getenv('LNPAYSECRETKEY'); 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.lnpay.co/v1/wallet/wal_PaPmH8HNrYQw7g/invoice');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_json);
        curl_setopt($ch, CURLOPT_USERPWD, $secret);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $result_decode = json_decode($result);
        $text = $result_decode->payment_request;
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            die();
        }else {
            $path = 'image/qrcodes/';
            $file_name = uniqid().".png";
            $file = $path.$file_name;
            $ecc = 'L';
            $pixel_Size = 20;
            $frame_Size = 20;
            QRcode::png($text, $file, $ecc, $pixel_Size, $frame_size);
        }
        curl_close($ch);

        return $file;
    }

    if($minUberLyftNetPay < $uberLyftEstimatePay){
        $uberLyftNetPay = $uberLyftEstimatePay;
    }else{
        $uberLyftNetPay = $minUberLyftNetPay;
    }

    $distanceInMeters = $response_all->rows[0]->elements[0]->distance->value;
    $timeInSecondes = $response_all->rows[0]->elements[0]->duration->value;

    $distanceInMiles = $distanceInMeters * 0.00062137;
    $distanceInMiles = ceil($distanceInMiles);
    $timeinMin = $timeInSecondes / 60;
    $timeinMin = ceil($timeinMin);    

    //Pre dead miles data
    $predistanceInMeters = $predeadresponse_all->rows[0]->elements[0]->distance->value;
    $pretimeInSecondes = $predeadresponse_all->rows[0]->elements[0]->duration->value;
    $predistanceInMiles = $predistanceInMeters * 0.00062137;
    $predistanceInMiles = ceil($predistanceInMiles);
    $pretimeinMin = $pretimeInSecondes / 60;
    $pretimeinMin = ceil($pretimeinMin);

    //Post dead miles data
    $postdistanceInMeters = $postdeadresponse_all->rows[0]->elements[0]->distance->value;
    $posttimeInSecondes = $postdeadresponse_all->rows[0]->elements[0]->duration->value;
    $postdistanceInMiles = $postdistanceInMeters * 0.00062137;
    $postdistanceInMiles = ceil($postdistanceInMiles);
    $posttimeinMin = $posttimeInSecondes / 60;
    $posttimeinMin = ceil($posttimeinMin);

    $totalEstdMiles = $predistanceInMiles+$postdistanceInMiles;
    $totalEstdMin = $pretimeinMin+$posttimeinMin;


    $rideType = $_POST['vehicleType'];
    $rideTypeArr['a'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'a', false);
    $rideTypeArr['XL'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'XL', false);
    $rideTypeArr['new'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'new', false);
    $rideTypeArr['new XL'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'new XL', false);
    $rideTypeArr['Limo'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'Limo', false);
    $rideTypeArr['Tow Truck'] = cal_estimated_cost($timeinMin, $distanceInMiles, 'Tow Truck', false);
    $rideTypeArr['selected'] = cal_estimated_cost($timeinMin, $distanceInMiles, $rideType, false);
    $rideTypeArr['estimated'] = cal_estimated_cost($timeinMin, $distanceInMiles, $rideType, true);
    $rideTypeArr['predeadestimate'] = cal_estimated_cost($pretimeinMin, $predistanceInMiles, 'pre dead', false);
    $rideTypeArr['postdeadestimate'] = cal_estimated_cost($posttimeinMin, $postdistanceInMiles, 'post dead', false);

    $rideTypeArr['tipAmt'] = ceil(($rideTypeArr['estimated'] * $tip) / 100);
    $origArray = $rideTypeArr;
    unset($rideTypeArr[$rideType]);
    $estimate = ceil($rideTypeArr['estimated']);
    $estimateQR = satoshiQR($estimate);
    $predeadestimate = ceil($rideTypeArr['predeadestimate']);
    $postdeadestimate = ceil($rideTypeArr['postdeadestimate']);
    $rideTypeArr['uberlyftpay'] = uberLyftPay($timeinMin, $distanceInMiles, $rideType, true);
    $tipAmount = $rideTypeArr['tipAmt'];
    $calculation = $rideTypeArr['selected'];
    $total = $predeadestimate + $postdeadestimate + $calculation;
    $uberLyftNetPay = $rideTypeArr['uberlyftpay'];
    $rideTypes = array(
        'a'         => 'a Ride (1-4 people) $2 per mile .30 per minute $10 MINIMUM',
        'XL'        => 'XL Ride (5-7 people More Room) $2.50 per mile .30 per minute $12 MINIMUM',
        'new'       => 'new Ride (1-4 people 3 Years or Newer) $3 per mile .30 per minute $15 MINIMUM',
        'new XL'    => 'new XL Ride (5-7 More Room 3 Years or Newer Model $3.50 per mile . 30 per minute $20 MINIMUM',
        'Limo'      => 'Limo Party Shuttle Bus 1-10+ $5 per mile .50 per minute $100 MINIMUM',
        'Tow Truck' => 'Tow Truck $5 per mile .50 per minute $50 minimum includes Jump Start / Gas / Lock Out Service'
    );

    $payment_mode = explode(' | ', $_POST['paymentMode']);
    if($payment_mode[0] === 'Cash'){
        $lightning_add = explode(' =', $payment_mode[1]);
        $payment_url = $payment_mode[0].' | '.'<a href="lightning:airportrides@sats.pm" target="_blank">'.$lightning_add[0].'</a> ='.$lightning_add[1];
    }else{
        $payment_url = $_POST['paymentMode'];
    }

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once('tcp/tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Zuber Alam');
$pdf->SetTitle('Universal Taxi Cab Errand Calculator USA - Independent Contractors');
$pdf->SetSubject('Airportride');
$pdf->SetKeywords('Airportride');

// set default header data
// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('dejavusans', '', 11, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html = '
<h1>Universal Taxi Cab, Delivery, & Errand Calculator USA - Independent Contractors</h1>
<table autosize="1" style="overflow: wrap;border:1px solid #000;">
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold">Pickup Location </span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold"><a href="https://maps.google.com/maps?q='.$_POST['pickupLocationLink'].'">'.$_POST['pickupLocation'].'</a></span></td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Date and Time</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['dateTime'].' '.$_POST['timeHours'].':'.$_POST['timeMinutes'].' '.$_POST['timeFormat'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">In or near</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['majorLocation'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold">Drop Location </span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold"><a href="https://maps.google.com/maps?q='.$_POST['dropLocationLink'].'">'.$_POST['dropLocation'].'</a></span></td>
        </tr>
        <tr>
            <td width="60%" style="border:1px solid #000;">Pre Dead Miles & Minutes</td>
            <td width="40%" style="border:1px solid #000;">'.$predistanceInMiles.' miles,'.$pretimeinMin.'mins</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold">Trip Miles & Minutes</span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold">'.$distanceInMiles.' miles, '.$timeinMin.' mins</span></td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold">Trip Estimated Cost</span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold">$'.number_format((float)$estimate, 2, ".","").'</span></td>
        </tr>
        <tr>
            <td width="60%" style="border:1px solid #000;">Post Dead Miles & Minutes</td>
            <td width="40%" style="border:1px solid #000;">'.$postdistanceInMiles.' miles,'.$posttimeinMin.'mins</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Tip to driver</td>
            <td width="40%" style="border:1px solid #000;">$'.number_format((float)$tipAmount, 2, ".","").'</td>
        </tr>
        
        <tr>
            <td width="60%" style="border:1px solid #000;">uber lyft NET pay</td>
            <td width="40%" style="border:1px solid #000;">$'.number_format((float)$uberLyftNetPay, 2, ".","").'</td>
        </tr>

        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Total Estimated Dead Miles & Minutes</td>
            <td width="40%" style="border:1px solid #000;">'.$totalEstdMiles.' miles,'.$totalEstdMin.'mins</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold">Vehicle Type (Driver Gets 90+%)</span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold">'.$rideTypes[$_POST['vehicleType']].'</span></td>
        </tr>
        <tr style="border:1px solid #000;">
        <td width="100%" style="border:1px solid #000;">Other Vehicle Type Estimated Cost</td>
    </tr>
        <tr style="border:1px solid #000;">';
        foreach($rideTypeArr as $key => $value ){
            if($key == 'selected' || $key == 'estimated' || $key == 'tipAmt' || $key == 'uberlyftpay' || $key == 'predeadestimate' || $key == 'postdeadestimate'){
                '';
            }
            else{
                $html .= '<td width="20%" style="border:1px solid #000;"> '.$key. ' Ride - $'.$value.'</td>';
            }
        }
        $html .= '
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Name</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['userName'].'</td>
        </tr>

        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Email</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['userEmail'].'</td>
        </tr>

        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Phone Number</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['userContact'].'</td>
        </tr>

        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">AirLine (If Applicable)</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['airLine'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Bags</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['bags'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Special Instructions</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['instructions'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;"><span style="font-weight:bold"># of Riders</span></td>
            <td width="40%" style="border:1px solid #000;"><span style="font-weight:bold">'.$_POST['riders'].'</span></td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Pets</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['pets'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Safety seats for child</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['child'].'</td>
        </tr>

        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">DELIVERY $10-50+ Over Receipts</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['select30'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">I am willing to PAY</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['select46'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Special Delivery Instructions</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['dInstructions'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Coming Soon You\'re Hired. Independent Contractors</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['select47'].'</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Tip</td>
            <td width="40%" style="border:1px solid #000;">'.$_POST['tip'].'%</td>
        </tr>
        <tr style="border:1px solid #000;">
            <td width="60%" style="border:1px solid #000;">Payment Mode</td>
            <td width="40%" style="border:1px solid #000;">'.$payment_url.'</td>
        </tr>
    </table>
';
if($estimateQR != ''){
$html .= '
        <p>Scan QRCode to pay using satoshis</p>
        <p><img src="'.$estimateQR.'" width="120" height="120"></p>
';
}
// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------
// $semi_rand = md5(time());
$pickup_location = explode(',', $_POST['pickupLocation']);
$drop_location = explode(',', $_POST['dropLocation']);
$save_file_name = date('d').'_'.date('m').'_'.date('Y').'_'.$_POST['majorLocation'].'_'.$pickup_location[0].'_'.$drop_location[0].'_'.number_format((float)$estimate, 2, ".","").'_'.uniqid().'_'.rand(1,1000);
$filename = "/pdf/".$save_file_name.".pdf";
ob_clean();
$fileatt = $pdf->Output(__DIR__ .$filename, 'FI');


// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = 3;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = getenv('GMAIL');                     // SMTP username
    $mail->Password   = getenv('PASSWORD');                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
    //Recipients
    $mail->setFrom('airportridestoday@gmail.com', 'Airportrides today');
    $mail->addAddress($_POST['userEmail'], $_POST['userName']);     // Add a recipient
    $mail->addAddress('airportridestoday@gmail.com');     // Add 2nd recipient

    // Attachments
    $mail->addAttachment(__DIR__.$filename);         // Add attachments

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'TaxiCab.tech Delivery Errand AirportRides.Today Estimate';
    $body = '<html>
    <head>
   <style>
   .container{
       background:#f6f6f6;
       padding:20px;
       border-radius:10px;
   }
   #table{
       width:100%;
   }
   #table,td,th{
       border-collapse:collapse;
    }
  td{
      border:1px solid #000;
  }
  tr{
    border:1px solid #000;
  }
  #table .ride-row .ride-row{
      width:20px;
  }
   </style>
    </head>
    <div class="container">
    <h1>Universal Taxi Cab, Delivery, & Errand Calculator USA - Independent Contractors</h1>
    <table id="table" autosize="1" style="overflow: wrap;border:1px solid #000;">
            <tr>
                <td colspan="3"><span style="font-weight:bold">Pickup Location </span></td>
                <td colspan="2"><span style="font-weight:bold"><a href="https://maps.google.com/maps?q='.$_POST['pickupLocationLink'].'">'.$_POST['pickupLocation'].'</a></span></td>
            </tr>
            <tr>
                <td colspan="3">Date and Time</td>
                <td colspan="2">'.$_POST['dateTime'].' '.$_POST['timeHours'].':'.$_POST['timeMinutes'].' '.$_POST['timeFormat'].'</td>
            </tr>
            <tr>
                <td colspan="3">In or near</td>
                <td colspan="2">'.$_POST['majorLocation'].'</td>
            </tr>
            <tr>
                <td colspan="3"><span style="font-weight:bold">Drop Location </span></td>
                <td colspan="2"><span style="font-weight:bold"><a href="https://maps.google.com/maps?q='.$_POST['dropLocationLink'].'">'.$_POST['dropLocation'].'</a></span></td>
            </tr>
            <tr>
                <td colspan="3">Pre Dead Miles & Minutes</td>
                <td colspan="2">'.$predistanceInMiles.' miles,'.$pretimeinMin.'mins</td>
            </tr>
            <tr>
                <td colspan="3"><span style="font-weight:bold">Trip Estimated Miles & Minutes</span></td>
                <td colspan="2"><span style="font-weight:bold">'.$distanceInMiles.' miles, '.$timeinMin.' mins</span></td>
            </tr>
            <tr>
                <td colspan="3"><span style="font-weight:bold">Trip Estimated Cost</span></td>
                <td colspan="2"><span style="font-weight:bold">$'.number_format((float)$estimate, 2, ".","").'</span></td>
            </tr>
            <tr>
                <td colspan="3">Post Dead Miles & Minutes</td>
                <td colspan="2">'.$postdistanceInMiles.' miles,'.$posttimeinMin.'mins</td>
            </tr>
            <tr>
                <td colspan="3">Tip to driver</td>
                <td colspan="2">$'.number_format((float)$tipAmount, 2, ".","").'</td>
            </tr>
            <tr>
                <td colspan="3">uber lyft NET pay</td>
                <td colspan="2">$'.number_format((float)$uberLyftNetPay, 2, ".","").'</td>
            </tr>
            <tr>
                <td colspan="3">Total Estimated Dead Miles & Minutes</td>
                <td colspan="2">'.$totalEstdMiles.' miles,'.$totalEstdMin.'mins</td>
            </tr>
            <tr>
                <td colspan="3"><span style="font-weight:bold">Vehicle Type (Driver Gets 90+%)</span></td>
                <td colspan="2"><span style="font-weight:bold">'.$rideTypes[$_POST['vehicleType']].'</span></td>
            </tr>
            <tr>
            <td width="100%" colspan="5">Other Vehicle Type Estimated Cost</td>
        </tr>
            <tr class="ride-row" width="100%">';
            foreach($rideTypeArr as $key => $value ){
                if($key == 'selected' || $key == 'estimated' || $key == 'tipAmt' || $key == 'uberlyftpay' || $key == 'predeadestimate' || $key == 'postdeadestimate'){
                    '';
                }
                else{
                    $body .= '<td width="20%"> '.$key. ' Ride - $'.$value.'</td>';
                }
            }
            $body .= '
            </tr>
            <tr>
                <td colspan="3">Name</td>
                <td colspan="2">'.$_POST['userName'].'</td>
            </tr>
    
            <tr>
                <td colspan="3">Email</td>
                <td colspan="2">'.$_POST['userEmail'].'</td>
            </tr>
    
            <tr>
                <td colspan="3">Phone Number</td>
                <td colspan="2">'.$_POST['userContact'].'</td>
            </tr>
    
            <tr>
                <td colspan="3">AirLine (If Applicable)</td>
                <td colspan="2">'.$_POST['airLine'].'</td>
            </tr>
            <tr>
                <td colspan="3">Bags</td>
                <td colspan="2">'.$_POST['bags'].'</td>
            </tr>
            <tr>
                <td colspan="3">Special Instructions</td>
                <td colspan="2">'.$_POST['instructions'].'</td>
            </tr>
            <tr>
                <td colspan="3"><span style="font-weight:bold"># of Riders</span></td>
                <td colspan="2"><span style="font-weight:bold">'.$_POST['riders'].'</span></td>
            </tr>
            <tr>
                <td colspan="3">Pets</td>
                <td colspan="2">'.$_POST['pets'].'</td>
            </tr>
            <tr>
                <td colspan="3">Safety seats for child</td>
                <td colspan="2">'.$_POST['child'].'</td>
            </tr>
    
            <tr>
                <td colspan="3">DELIVERY $10-50+ Over Receipts</td>
                <td colspan="2">'.$_POST['select30'].'</td>
            </tr>
            <tr>
                <td colspan="3">I am willing to PAY</td>
                <td colspan="2">'.$_POST['select46'].'</td>
            </tr>
            <tr>
                <td colspan="3">Special Delivery Instructions</td>
                <td colspan="2">'.$_POST['dInstructions'].'</td>
            </tr>
            <tr>
                <td colspan="3">Coming Soon You\'re Hired. Independent Contractors</td>
                <td colspan="2">'.$_POST['select47'].'</td>
            </tr>
            <tr>
                <td colspan="3">Tip</td>
                <td colspan="2">'.$_POST['tip'].'%</td>
            </tr>
            <tr>
                <td colspan="3">Payment Mode</td>
                <td colspan="2">'.$_POST['paymentMode'].'</td>
            </tr>
        </table>
        </div>
        </html>';
    $mail->Body  = $body; 

    $mail->send();
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>free.TaxiCab.Tech Aiportrides.Today RoomService.guru you're hired FREE Web App uber alternative, lyft alternative, uber eats alternative, doordash alternative, grubhub alternative, postmates alternative, instacart alternative...TLDR  uber, lyft, uber eats, instacart, grubhub, postmates, insert "gig" app here, without the illegal predatory 1960s wages, fraud, & being "worth" billions or what they would cost if sillyCON valley apps were required to pay legal wages from 2004 & follow labor laws & human rights...</title>
<link rel="stylesheet" href="./css/bootstrap.min.css">
<link href="./css/smart_wizard_all.min.css" rel="stylesheet" type="text/css" />
    <style>
        #map {
            height: 100%;
        }
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .main-form.col-md-12.col-lg-12.col-sm-12 {
            margin-top: 2%;
            background: #f6f6f6;
            padding: 20px;
            border-radius: 15px;
        }

        .delivery-fields.col-md-12.col-lg-12.col-sm-12 {
            margin-top: 2%;
            background: #abc0e0;
            /* padding: 20px; */
            border-radius: 15px;
        }

        .coming-soon-select{
            background:#D5FFBA;
            border-radius: 15px;
            margin-top: 2%;
            padding:20px;
        }

        .payment-mode-section{
            background:#f6f6f6;
            border-radius: 15px;
            margin-top: 2%;
            padding:20px;
        }

        .row h2 {
            width: 100%;
            border-bottom: 1px solid #c6c6c6;
            padding-bottom: 24px;
        }
        .header-section{
            margin-top:1%;
            border-radius: 15px;
        }
        .footer-section{
            background: #000;
            margin:4% 0% 4% 0%;
            border-radius: 15px;
            padding:20px;
        }
        .top-header{
            margin-top:1%;
            font-size:10px;
        }
        .fare-data-table{
            background: #c6c6c6;
            margin:4% 0% 4% 0%;
            border-radius: 15px;
            padding:20px;
            overflow-x:scroll;
        }
        .fare-data-table table,td,th{
            border:1px solid;
        }
    .style1 {font-weight: bold}
    </style>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC0BPzIIlQUNJgvge9EpSstUS4MbKPPSLo&libraries=places"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script
      src="https://unpkg.com/pay-with-ln@0.1.0/dist/pay-with-ln.js"
      integrity="sha384-Uid8n0M8dpAoE1SOQOXOcMfDy9hvqtSp+A3xMFilQn+Z6fxsnmCayPVP8na5vdAv"
      crossorigin="anonymous"
    ></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
</head>

<body>
<div class="container top-header">
<p class="text-center">FREE.TaxiCab.Tech Real Time Email Dispatch <a href="https://taxicabdelivery.online/2022/lite.php">Lite</a> | <a href="https://taxicabdelivery.online/4sale/">4sale Local</a> | <a href="https://taxicabdelivery.online/united/menus/index.php">Menus</a> | <a href="http://denver.airportrides.today">Denver CO</a> | <a href="https://taxicabdelivery.online/pops/">POPS.airportrides.today Popular Saved Rides</a> | DELIVERY.RoomService.Guru <a href="https://taxicabdelivery.online/420/">PIVOT.weedshare.tech</a> | <a href="https://taxicabdelivery.online/video/how2-taxicab.tech.mp4">how2.taxicab.tech video</a> | <a href="https://taxicabdelivery.online/united/menus/taxicab.tech-how-to.jpg">How To Use This FREE Form in 1 picture</a> | <a href="https://taxicabdelivery.online/image/uberlyft-compare-tool.jpg">Home Destination Zip Code / Dead Mile Calculator & uber lyft Compare Tool</a> | This webpage/app/form duplicates hundreds of billions "worth" of sillyCON valley gig "tech" minus the illegal predatory Fraud... | <a href="https://taxicabdelivery.online/2022/image/qr-crypto.taxicab.tech-mobile-wallet.jpg">Pay with CRYPTO.taxicab.tech </a> or pay.topnotch.net<select name="denver.airportrides.today" id="denver.airportrides.today" onchange="window.open(this.value, '_blank')">

<option value="http://denver.airportrides.today">denver.airportrides.today</option>
<option value="https://taxicabdelivery.online/2022/image/qr-crypto.taxicab.tech-mobile-wallet.jpg">bitcoin lightning address = airportrides@sats.pm</option>
<option value="https://taxicabdelivery.online/2022/freebreakfast_dot_org/index.php">freebreakfast.org</option>
<option value="http://pay.airportrides.today">pay.airportrides.today = paypal</option>
<option value="https://www.walletofsatoshi.com/">wallet.airportrides.today</option>
<option value="https://taxicabdelivery.online/2022/99519.php">AK</option>
<option value="https://taxicabdelivery.online/2022/35212.php">AL</option>
<option value="https://taxicabdelivery.online/2022/72202.php">AR</option>
<option value="https://taxicabdelivery.online/2022/85260.php">AZ</option>
<option value="https://taxicabdelivery.online/2022/90043.php">CA LA</option>
<option value="https://taxicabdelivery.online/2022/92101.php">CA San Diego</option>
<option value="https://taxicabdelivery.online/2022/94218.php">CA San Francisco</option>
<option value="https://taxicabdelivery.online/2022/80249.php">CO</option>
<option value="https://taxicabdelivery.online/2022/06103.php">CT</option>
<option value="https://taxicabdelivery.online/2022/22202.php">DC</option>
<option value="https://taxicabdelivery.online/2022/19720.php">DE</option>
<option value="https://taxicabdelivery.online/2022/33126.php">FL</option>
<option value="https://taxicabdelivery.online/2022/30320.php">GA</option>
<option value="https://taxicabdelivery.online/2022/96815.php">HI</option>
<option value="https://taxicabdelivery.online/2022/50321.php">IA</option>
<option value="https://taxicabdelivery.online/2022/83705.php">ID</option>
<option value="https://taxicabdelivery.online/2022/60666.php">IL</option>
<option value="https://taxicabdelivery.online/2022/46241.php">IN</option>
<option value="https://taxicabdelivery.online/2022/64153.php">KS</option>
<option value="https://taxicabdelivery.online/2022/40209.php">KY</option>
<option value="https://taxicabdelivery.online/2022/70062.php">LA</option>
<option value="https://taxicabdelivery.online/2022/02128.php">MA</option>
<option value="https://taxicabdelivery.online/2022/21240.php">MD</option>
<option value="https://taxicabdelivery.online/2022/04102.php">ME</option>
<option value="https://taxicabdelivery.online/2022/48174.php">MI</option>
<option value="https://taxicabdelivery.online/2022/55450.php">MN</option>
<option value="https://taxicabdelivery.online/2022/63145.php">MO</option>
<option value="https://taxicabdelivery.online/2022/39208.php">MS</option>
<option value="https://taxicabdelivery.online/2022/59105.php">MT</option>
<option value="https://taxicabdelivery.online/2022/28208.php">NC</option>
<option value="https://taxicabdelivery.online/2022/58102.php">ND</option>
<option value="https://taxicabdelivery.online/2022/68110.php">NE</option>
<option value="https://taxicabdelivery.online/2022/03103.php">NH</option>
<option value="https://taxicabdelivery.online/2022/08234.php">NJ</option>
<option value="https://taxicabdelivery.online/2022/87106.php">NM</option>
<option value="https://taxicabdelivery.online/2022/89119.php">NV</option>
<option value="https://taxicabdelivery.online/2022/10036.php">NY</option>
<option value="https://taxicabdelivery.online/2022/44142.php">OH</option>
<option value="https://taxicabdelivery.online/2022/73159.php">OK</option>
<option value="https://taxicabdelivery.online/2022/97218.php">OR</option>
<option value="https://taxicabdelivery.online/2022/19153.php">PA</option>
<option value="https://taxicabdelivery.online/2022/02886.php">RI</option>
<option value="https://taxicabdelivery.online/2022/29418.php">SC</option>
<option value="https://taxicabdelivery.online/2022/57106.php">SD</option>
<option value="https://taxicabdelivery.online/2022/37214.php">TN</option>
<option value="https://taxicabdelivery.online/2022/78719.php">TX Austin</option>
<option value="https://taxicabdelivery.online/2022/75261.php">TX Dallas</option>
<option value="https://taxicabdelivery.online/2022/77032.php">TX Houston</option>
<option value="https://taxicabdelivery.online/2022/78216.php">TX San Antonio</option>
<option value="http://topnotch.stream">TopNotch</option>
<option value="https://taxicabdelivery.online/2022/84122.php">UT</option>
<option value="https://taxicabdelivery.online/2022/23220.php">VA</option>
<option value="https://taxicabdelivery.online/2022/05403.php">VT</option>
<option value="https://taxicabdelivery.online/2022/98158.php">WA</option>
<option value="https://taxicabdelivery.online/2022/53207.php">WI</option>
<option value="https://taxicabdelivery.online/2022/25311.php">WV</option>
<option value="https://taxicabdelivery.online/2022/82001.php">WY</option>
<option value="http://aberdeen.airportrides.today">aberdeen.airportrides.today</option>
<option value="http://abilene.airportrides.today">abilene.airportrides.today</option>
<option value="http://akron.airportrides.today">akron.airportrides.today</option>
<option value="http://alaska.airportrides.today">alaska.airportrides.today</option>
<option value="http://albuquerque.airportrides.today">albuquerque.airportrides.today</option>
<option value="http://alexandria.airportrides.today">alexandria.airportrides.today</option>
<option value="http://allentown.airportrides.today">allentown.airportrides.today</option>
<option value="http://alliante.airportrides.today">alliante.airportrides.today</option>
<option value="http://amarillo.airportrides.today">amarillo.airportrides.today</option>
<option value="http://anaheim.airportrides.today">anaheim.airportrides.today</option>
<option value="http://anchorage.airportrides.today">anchorage.airportrides.today</option>
<option value="http://annarbor.airportrides.today">annarbor.airportrides.today</option>
<option value="http://antioch.airportrides.today">antioch.airportrides.today</option>
<option value="http://aria.airportrides.today">aria.airportrides.today</option>
<option value="http://arizona.airportrides.today">arizona.airportrides.today</option>
<option value="http://arkansas.airportrides.today">arkansas.airportrides.today</option>
<option value="http://arlington.airportrides.today">arlington.airportrides.today</option>
<option value="http://arlington.airportrides.today">arlington.airportrides.today</option>
<option value="http://arvada.airportrides.today">arvada.airportrides.today</option>
<option value="http://aspen.airportrides.today">aspen.airportrides.today</option>
<option value="http://athens.airportrides.today">athens.airportrides.today</option>
<option value="http://atlanta.airportrides.today">atlanta.airportrides.today</option>
<option value="http://atlanticcity.airportrides.today">atlanticcity.airportrides.today</option>
<option value="http://augusta.airportrides.today">augusta.airportrides.today</option>
<option value="http://aurora.airportrides.today">aurora.airportrides.today</option>
<option value="http://aurora.airportrides.today">aurora.airportrides.today</option>
<option value="http://austin.airportrides.today">austin.airportrides.today</option>
<option value="http://bakersfield.airportrides.today">bakersfield.airportrides.today</option>
<option value="http://baltimore.airportrides.today">baltimore.airportrides.today</option>
<option value="http://batonrouge.airportrides.today">batonrouge.airportrides.today</option>
<option value="http://beaumont.airportrides.today">beaumont.airportrides.today</option>
<option value="http://bedford.airportrides.today">bedford.airportrides.today</option>
<option value="http://beecave.airportrides.today">beecave.airportrides.today</option>
<option value="http://bellagio.airportrides.today">bellagio.airportrides.today</option>
<option value="http://bellevue.airportrides.today">bellevue.airportrides.today</option>
<option value="http://berea.airportrides.today">berea.airportrides.today</option>
<option value="http://berkeley.airportrides.today">berkeley.airportrides.today</option>
<option value="http://billings.airportrides.today">billings.airportrides.today</option>
<option value="http://birmingham.airportrides.today">birmingham.airportrides.today</option>
<option value="http://blackhawk.airportrides.today">blackhawk.airportrides.today</option>
<option value="http://boca.airportrides.today">boca.airportrides.today</option>
<option value="http://boise.airportrides.today">boise.airportrides.today</option>
<option value="http://boston.airportrides.today">boston.airportrides.today</option>
<option value="http://boulder.airportrides.today">boulder.airportrides.today</option>
<option value="http://bowlinggreen.airportrides.today">bowlinggreen.airportrides.today</option>
<option value="http://brecknridge.airportrides.today">brecknridge.airportrides.today</option>
<option value="http://brentwood.airportrides.today">brentwood.airportrides.today</option>
<option value="http://bridgeport.airportrides.today">bridgeport.airportrides.today</option>
<option value="http://bronx.airportrides.today">bronx.airportrides.today</option>
<option value="http://brookings.airportrides.today">brookings.airportrides.today</option>
<option value="http://brooklyn.airportrides.today">brooklyn.airportrides.today</option>
<option value="http://brookpark.airportrides.today">brookpark.airportrides.today</option>
<option value="http://brownsville.airportrides.today">brownsville.airportrides.today</option>
<option value="http://buda.airportrides.today">buda.airportrides.today</option>
<option value="http://buffalo.airportrides.today">buffalo.airportrides.today</option>
<option value="http://burbank.airportrides.today">burbank.airportrides.today</option>
<option value="http://burlington.airportrides.today">burlington.airportrides.today</option>
<option value="http://caesarspalace.airportrides.today">caesarspalace.airportrides.today</option>
<option value="http://california.airportrides.today">california.airportrides.today</option>
<option value="http://cambridge.airportrides.today">cambridge.airportrides.today</option>
<option value="http://canton.airportrides.today">canton.airportrides.today</option>
<option value="http://capecoral.airportrides.today">capecoral.airportrides.today</option>
<option value="http://carrollton.airportrides.today">carrollton.airportrides.today</option>
<option value="http://carsoncity.airportrides.today">carsoncity.airportrides.today</option>
<option value="http://cary.airportrides.today">cary.airportrides.today</option>
<option value="http://casper.airportrides.today">casper.airportrides.today</option>
<option value="http://castlerock.airportrides.today">castlerock.airportrides.today</option>
<option value="http://cedarpark.airportrides.today">cedarpark.airportrides.today</option>
<option value="http://cedarrapids.airportrides.today">cedarrapids.airportrides.today</option>
<option value="http://centennial.airportrides.today">centennial.airportrides.today</option>
<option value="http://chandler.airportrides.today">chandler.airportrides.today</option>
<option value="http://charleston.airportrides.today">charleston.airportrides.today</option>
<option value="http://charleston.airportrides.today">charleston.airportrides.today</option>
<option value="http://charlotte.airportrides.today">charlotte.airportrides.today</option>
<option value="http://chattanooga.airportrides.today">chattanooga.airportrides.today</option>
<option value="http://chesapeake.airportrides.today">chesapeake.airportrides.today</option>
<option value="http://cheyenne.airportrides.today">cheyenne.airportrides.today</option>
<option value="http://chicago.airportrides.today">chicago.airportrides.today</option>
<option value="http://chulavista.airportrides.today">chulavista.airportrides.today</option>
<option value="http://cincinnati.airportrides.today">cincinnati.airportrides.today</option>
<option value="http://circuscircus.airportrides.today">circuscircus.airportrides.today</option>
<option value="http://clarksville.airportrides.today">clarksville.airportrides.today</option>
<option value="http://clearwater.airportrides.today">clearwater.airportrides.today</option>
<option value="http://cleveland.airportrides.today">cleveland.airportrides.today</option>
<option value="http://clevelandheights.airportrides.today">clevelandheights.airportrides.today</option>
<option value="http://cody.airportrides.today">cody.airportrides.today</option>
<option value="http://colorado.airportrides.today">colorado.airportrides.today</option>
<option value="http://coloradosprings.airportrides.today">coloradosprings.airportrides.today</option>
<option value="http://columbia.airportrides.today">columbia.airportrides.today</option>
<option value="http://columbia.airportrides.today">columbia.airportrides.today</option>
<option value="http://columbus.airportrides.today">columbus.airportrides.today</option>
<option value="http://concord.airportrides.today">concord.airportrides.today</option>
<option value="http://connecticut.airportrides.today">connecticut.airportrides.today</option>
<option value="http://coralsprings.airportrides.today">coralsprings.airportrides.today</option>
<option value="http://corona.airportrides.today">corona.airportrides.today</option>
<option value="http://corpuschristi.airportrides.today">corpuschristi.airportrides.today</option>
<option value="http://costamesa.airportrides.today">costamesa.airportrides.today</option>
<option value="http://cuyahogafalls.airportrides.today">cuyahogafalls.airportrides.today</option>
<option value="http://dallas.airportrides.today">dallas.airportrides.today</option>
<option value="http://dalycity.airportrides.today">dalycity.airportrides.today</option>
<option value="http://dayton.airportrides.today">dayton.airportrides.today</option>
<option value="http://dc.airportrides.today">dc.airportrides.today</option>
<option value="http://delaware.airportrides.today">delaware.airportrides.today</option>
<option value="http://denton.airportrides.today">denton.airportrides.today</option>
<option value="http://denver.airportrides.today">denver.airportrides.today</option>
<option value="http://denver.airportrides.today">denver.airportrides.today</option>
<option value="http://desmoines.airportrides.today">desmoines.airportrides.today</option>
<option value="http://detroit.airportrides.today">detroit.airportrides.today</option>
<option value="http://donelson.airportrides.today">donelson.airportrides.today</option>
<option value="http://dover.airportrides.today">dover.airportrides.today</option>
<option value="http://downey.airportrides.today">downey.airportrides.today</option>
<option value="http://dtc.airportrides.today">dtc.airportrides.today</option>
<option value="http://durham.airportrides.today">durham.airportrides.today</option>
<option value="http://eastcleveland.airportrides.today">eastcleveland.airportrides.today</option>
<option value="http://elgin.airportrides.today">elgin.airportrides.today</option>
<option value="http://elizabeth.airportrides.today">elizabeth.airportrides.today</option>
<option value="http://elkgrove.airportrides.today">elkgrove.airportrides.today</option>
<option value="http://elmonte.airportrides.today">elmonte.airportrides.today</option>
<option value="http://elpaso.airportrides.today">elpaso.airportrides.today</option>
<option value="http://elyria.airportrides.today">elyria.airportrides.today</option>
<option value="http://encore.airportrides.today">encore.airportrides.today</option>
<option value="http://englewood.airportrides.today">englewood.airportrides.today</option>
<option value="http://erie.airportrides.today">erie.airportrides.today</option>
<option value="http://escondido.airportrides.today">escondido.airportrides.today</option>
<option value="http://euclid.airportrides.today">euclid.airportrides.today</option>
<option value="http://eugene.airportrides.today">eugene.airportrides.today</option>
<option value="http://evanston.airportrides.today">evanston.airportrides.today</option>
<option value="http://evansville.airportrides.today">evansville.airportrides.today</option>
<option value="http://everett.airportrides.today">everett.airportrides.today</option>
<option value="http://fairfield.airportrides.today">fairfield.airportrides.today</option>
<option value="http://fargo.airportrides.today">fargo.airportrides.today</option>
<option value="http://farmington.airportrides.today">farmington.airportrides.today</option>
<option value="http://fayetteville.airportrides.today">fayetteville.airportrides.today</option>
<option value="http://flint.airportrides.today">flint.airportrides.today</option>
<option value="http://florida.airportrides.today">florida.airportrides.today</option>
<option value="http://fontana.airportrides.today">fontana.airportrides.today</option>
<option value="http://fortcollins.airportrides.today">fortcollins.airportrides.today</option>
<option value="http://fortlauderdale.airportrides.today">fortlauderdale.airportrides.today</option>
<option value="http://fortwayne.airportrides.today">fortwayne.airportrides.today</option>
<option value="http://fortworth.airportrides.today">fortworth.airportrides.today</option>
<option value="http://franklin.airportrides.today">franklin.airportrides.today</option>
<option value="http://fredericksburg.airportrides.today">fredericksburg.airportrides.today</option>
<option value="http://fremont.airportrides.today">fremont.airportrides.today</option>
<option value="http://fresno.airportrides.today">fresno.airportrides.today</option>
<option value="http://frisco.airportrides.today">frisco.airportrides.today</option>
<option value="http://fullerton.airportrides.today">fullerton.airportrides.today</option>
<option value="http://gainesville.airportrides.today">gainesville.airportrides.today</option>
<option value="http://gallatin.airportrides.today">gallatin.airportrides.today</option>
<option value="http://gardengrove.airportrides.today">gardengrove.airportrides.today</option>
<option value="http://garfieldheights.airportrides.today">garfieldheights.airportrides.today</option>
<option value="http://garland.airportrides.today">garland.airportrides.today</option>
<option value="http://georgia.airportrides.today">georgia.airportrides.today</option>
<option value="http://gilbert.airportrides.today">gilbert.airportrides.today</option>
<option value="http://gillette.airportrides.today">gillette.airportrides.today</option>
<option value="http://glendale.airportrides.today">glendale.airportrides.today</option>
<option value="http://glendale.airportrides.today">glendale.airportrides.today</option>
<option value="http://glenwood.airportrides.today">glenwood.airportrides.today</option>
<option value="http://golden.airportrides.today">golden.airportrides.today</option>
<option value="http://grandprairie.airportrides.today">grandprairie.airportrides.today</option>
<option value="http://grandrapids.airportrides.today">grandrapids.airportrides.today</option>
<option value="http://greenbay.airportrides.today">greenbay.airportrides.today</option>
<option value="http://greenriver.airportrides.today">greenriver.airportrides.today</option>
<option value="http://greensboro.airportrides.today">greensboro.airportrides.today</option>
<option value="http://greenvalleyranch.airportrides.today">greenvalleyranch</option>
<option value="http://greenwich.airportrides.today">greenwich.airportrides.today</option>
<option value="http://gresham.airportrides.today">gresham.airportrides.today</option>
<option value="http://hamilton.airportrides.today">hamilton.airportrides.today</option>
<option value="http://hampton.airportrides.today">hampton.airportrides.today</option>
<option value="http://hartford.airportrides.today">hartford.airportrides.today</option>
<option value="http://harvard.airportrides.today">harvard.airportrides.today</option>
<option value="http://hawaii.airportrides.today">hawaii.airportrides.today</option>
<option value="http://hayward.airportrides.today">hayward.airportrides.today</option>
<option value="http://henderson.airportrides.today">henderson.airportrides.today</option>
<option value="http://henderson.airportrides.today">henderson.airportrides.today</option>
<option value="http://hermitage.airportrides.today">hermitage.airportrides.today</option>
<option value="http://hialeah.airportrides.today">hialeah</option>
<option value="http://highpoint.airportrides.today">highpoint.airportrides.today</option>
<option value="http://hills.airportrides.today">hills.airportrides.today</option>
<option value="http://hollywood.airportrides.today">hollywood.airportrides.today</option>
<option value="http://honolulu.airportrides.today">honolulu.airportrides.today</option>
<option value="http://houston.airportrides.today">houston.airportrides.today</option>
<option value="http://https://taxicabdelivery.online/2022/26062.php">weirtonheights 26062</option>
<option value="http://huntington.airportrides.today">huntington.airportrides.today</option>
<option value="http://huntington.airportrides.today">huntington.airportrides.today</option>
<option value="http://huntingtonbeach.airportrides.today">huntingtonbeach.airportrides.today</option>
<option value="http://huntsville.airportrides.today">huntsville.airportrides.today</option>
<option value="http://idaho.airportrides.today">idaho.airportrides.today</option>
<option value="http://illinois.airportrides.today">illinois.airportrides.today</option>
<option value="http://independence.airportrides.today">independence.airportrides.today</option>
<option value="http://indiana.airportrides.today">indiana.airportrides.today</option>
<option value="http://indianapolis.airportrides.today">indianapolis.airportrides.today</option>
<option value="http://inglewood.airportrides.today">inglewood.airportrides.today</option>
<option value="http://iowa.airportrides.today">iowa.airportrides.today</option>
<option value="http://irvine.airportrides.today">irvine.airportrides.today</option>
<option value="http://irving.airportrides.today">irving.airportrides.today</option>
<option value="http://jackson.airportrides.today">jackson.airportrides.today</option>
<option value="http://jackson.airportrides.today">jackson.airportrides.today</option>
<option value="http://jackson.airportrides.today">jackson.airportrides.today</option>
<option value="http://jacksonville.airportrides.today">jacksonville.airportrides.today</option>
<option value="http://jerseycity.airportrides.today">jerseycity.airportrides.today</option>
<option value="http://joliet.airportrides.today">joliet.airportrides.today</option>
<option value="http://kansas.airportrides.today">kansas.airportrides.today</option>
<option value="http://kansascity.airportrides.today">kansascity.airportrides.today</option>
<option value="http://kansascity.airportrides.today">kansascity.airportrides.today</option>
<option value="http://kent.airportrides.today">kent.airportrides.today</option>
<option value="http://kentucky.airportrides.today">kentucky.airportrides.today</option>
<option value="http://killeen.airportrides.today">killeen.airportrides.today</option>
<option value="http://kinsman.airportrides.today">kinsman.airportrides.today</option>
<option value="http://knoxville.airportrides.today">knoxville.airportrides.today</option>
<option value="http://la.airportrides.today">la</option>
<option value="http://lafayette.airportrides.today">lafayette.airportrides.today</option>
<option value="http://lakeway.airportrides.today">lakeway.airportrides.today</option>
<option value="http://lakewood.airportrides.today">lakewood.airportrides.today</option>
<option value="http://lakewood.airportrides.today">lakewood.airportrides.today</option>
<option value="http://lancaster.airportrides.today">lancaster.airportrides.today</option>
<option value="http://lansing.airportrides.today">lansing.airportrides.today</option>
<option value="http://laramie.airportrides.today">laramie.airportrides.today</option>
<option value="http://laredo.airportrides.today">laredo.airportrides.today</option>
<option value="http://lasvegas.airportrides.today">lasvegas.airportrides.today</option>
<option value="http://laughlin.airportrides.today">laughlin.airportrides.today</option>
<option value="http://leander.airportrides.today">leander.airportrides.today</option>
<option value="http://lebanon.airportrides.today">lebanon.airportrides.today</option>
<option value="http://littlerock.airportrides.today">littlerock.airportrides.today</option>
<option value="http://littleton.airportrides.today">littleton.airportrides.today</option>
<option value="http://longbeach.airportrides.today">longbeach.airportrides.today</option>
<option value="http://longmont.airportrides.today">longmont.airportrides.today</option>
<option value="http://lorain.airportrides.today">lorain.airportrides.today</option>
<option value="http://losangeles.airportrides.today">losangeles.airportrides.today</option>
<option value="http://louisiana.airportrides.today">louisiana.airportrides.today</option>
<option value="http://louisville.airportrides.today">louisville.airportrides.today</option>
<option value="http://lowell.airportrides.today">lowell.airportrides.today</option>
<option value="http://lubbock.airportrides.today">lubbock.airportrides.today</option>
<option value="http://luxor.airportrides.today">luxor.airportrides.today</option>
<option value="http://madison.airportrides.today">madison.airportrides.today</option>
<option value="http://maine.airportrides.today">maine.airportrides.today</option>
<option value="http://manchester.airportrides.today">manchester.airportrides.today</option>
<option value="http://mandalaybay.airportrides.today">mandalaybay.airportrides.today</option>
<option value="http://manhattan.airportrides.today">manhattan.airportrides.today</option>
<option value="http://mapleheights.airportrides.today">mapleheights.airportrides.today</option>
<option value="http://maryland.airportrides.today">maryland.airportrides.today</option>
<option value="http://massachusetts.airportrides.today">massachusetts.airportrides.today</option>
<option value="http://mcallen.airportrides.today">mcallen.airportrides.today</option>
<option value="http://mckinney.airportrides.today">mckinney.airportrides.today</option>
<option value="http://medina.airportrides.today">medina.airportrides.today</option>
<option value="http://memphis.airportrides.today">memphis.airportrides.today</option>
<option value="http://mentor.airportrides.today">mentor.airportrides.today</option>
<option value="http://menus.airportrides.today">menus.airportrides.today</option>
<option value="http://mesa.airportrides.today">mesa.airportrides.today</option>
<option value="http://mesquite.airportrides.today">mesquite.airportrides.today</option>
<option value="http://mgmgrand.airportrides.today">mgmgrand.airportrides.today</option>
<option value="http://miami.airportrides.today">miami.airportrides.today</option>
<option value="http://miamigardens.airportrides.today">miamigardens.airportrides.today</option>
<option value="http://michigan.airportrides.today">michigan.airportrides.today</option>
<option value="http://middleburgheights.airportrides.today">middleburgheights.airportrides.today</option>
<option value="http://midland.airportrides.today">midland.airportrides.today</option>
<option value="http://miles.airportrides.today">miles.airportrides.today</option>
<option value="http://milwaukee.airportrides.today">milwaukee.airportrides.today</option>
<option value="http://minneapolis.airportrides.today">minneapolis.airportrides.today</option>
<option value="http://minnesota.airportrides.today">minnesota.airportrides.today</option>
<option value="http://miramar.airportrides.today">miramar.airportrides.today</option>
<option value="http://mississippi.airportrides.today">mississippi.airportrides.today</option>
<option value="http://missouri.airportrides.today">missouri.airportrides.today</option>
<option value="http://mobile.airportrides.today">mobile.airportrides.today</option>
<option value="http://modesto.airportrides.today">modesto.airportrides.today</option>
<option value="http://molokai.airportrides.today">molokai.airportrides.today</option>
<option value="http://montana.airportrides.today">montana.airportrides.today</option>
<option value="http://montecarlo.airportrides.today">montecarlo.airportrides.today</option>
<option value="http://montgomery.airportrides.today">montgomery.airportrides.today</option>
<option value="http://monument.airportrides.today">monument.airportrides.today</option>
<option value="http://morenovalley.airportrides.today">morenovalley</option>
<option value="http://morgantown.airportrides.today">morgantown.airportrides.today</option>
<option value="http://mountjuliet.airportrides.today">mountjuliet.airportrides.today</option>
<option value="http://murfreesboro.airportrides.today">murfreesboro.airportrides.today</option>
<option value="http://murrieta.airportrides.today">murrieta.airportrides.today</option>
<option value="http://naperville.airportrides.today">naperville.airportrides.today</option>
<option value="http://nashville.airportrides.today">nashville.airportrides.today</option>
<option value="http://nebraska.airportrides.today">nebraska.airportrides.today</option>
<option value="http://nevada.airportrides.today">nevada.airportrides.today</option>
<option value="http://newark.airportrides.today">newark.airportrides.today</option>
<option value="http://newhampshire.airportrides.today">newhampshire.airportrides.today</option>
<option value="http://newhaven.airportrides.today">newhaven.airportrides.today</option>
<option value="http://newjersey.airportrides.today">newjersey.airportrides.today</option>
<option value="http://newmexico.airportrides.today">newmexico.airportrides.today</option>
<option value="http://neworleans.airportrides.today">neworleans.airportrides.today</option>
<option value="http://newportnews.airportrides.today">newportnews.airportrides.today</option>
<option value="http://newyork.airportrides.today">newyork.airportrides.today</option>
<option value="http://newyorkcity.airportrides.today">newyorkcity.airportrides.today</option>
<option value="http://norfolk.airportrides.today">norfolk.airportrides.today</option>
<option value="http://norman.airportrides.today">norman.airportrides.today</option>
<option value="http://northcarolina.airportrides.today">northcarolina.airportrides.today</option>
<option value="http://northdakota.airportrides.today">northdakota.airportrides.today</option>
<option value="http://northlasvegas.airportrides.today">northlasvegas.airportrides.today</option>
<option value="http://northolmsted.airportrides.today">northolmsted.airportrides.today</option>
<option value="http://northridgeville.airportrides.today">northridgeville</option>
<option value="http://northroyalton.airportrides.today">northroyalton.airportrides.today</option>
<option value="http://norwalk.airportrides.today">norwalk.airportrides.today</option>
<option value="http://nyc.airportrides.today">nyc</option>
<option value="http://oakland.airportrides.today">oakland.airportrides.today</option>
<option value="http://oceanside.airportrides.today">oceanside.airportrides.today</option>
<option value="http://ohio.airportrides.today">ohio.airportrides.today</option>
<option value="http://oklahoma.airportrides.today">oklahoma.airportrides.today</option>
<option value="http://oklahomacity.airportrides.today">oklahomacity.airportrides.today</option>
<option value="http://olathe.airportrides.today">olathe.airportrides.today</option>
<option value="http://oldhickory.airportrides.today">oldhickory.airportrides.today</option>
<option value="http://omaha.airportrides.today">omaha.airportrides.today</option>
<option value="http://ontario.airportrides.today">ontario.airportrides.today</option>
<option value="http://orange.airportrides.today">orange.airportrides.today</option>
<option value="http://orangemound.airportrides.today">orangemound.airportrides.today</option>
<option value="http://oregon.airportrides.today">oregon.airportrides.today</option>
<option value="http://orlando.airportrides.today">orlando.airportrides.today</option>
<option value="http://orleans.airportrides.today">orleans.airportrides.today</option>
<option value="http://overlandpark.airportrides.today">overlandpark.airportrides.today</option>
<option value="http://ovo.airportrides.today">ovo.airportrides.today</option>
<option value="http://oxnard.airportrides.today">oxnard.airportrides.today</option>
<option value="http://pahrump.airportrides.today">pahrump.airportrides.today</option>
<option value="http://paisleypark.airportrides.today">paisleypark.airportrides.today</option>
<option value="http://palazzo.airportrides.today">palazzo.airportrides.today</option>
<option value="http://palmbay.airportrides.today">palmbay.airportrides.today</option>
<option value="http://palmdale.airportrides.today">palmdale.airportrides.today</option>
<option value="http://palms.airportrides.today">palms.airportrides.today</option>
<option value="http://parker.airportrides.today">parker.airportrides.today</option>
<option value="http://parkersburg.airportrides.today">parkersburg.airportrides.today</option>
<option value="http://parma.airportrides.today">parma.airportrides.today</option>
<option value="http://pasadena.airportrides.today">pasadena.airportrides.today</option>
<option value="http://pembrokepines.airportrides.today">pembrokepines.airportrides.today</option>
<option value="http://pennsylvania.airportrides.today">pennsylvania.airportrides.today</option>
<option value="http://peoria.airportrides.today">peoria.airportrides.today</option>
<option value="http://peoria.airportrides.today">peoria.airportrides.today</option>
<option value="http://peterson.airportrides.today">peterson.airportrides.today</option>
<option value="http://pflugerville.airportrides.today">pflugerville.airportrides.today</option>
<option value="http://philadelphia.airportrides.today">philadelphia.airportrides.today</option>
<option value="http://phoenix.airportrides.today">phoenix.airportrides.today</option>
<option value="http://pittsburgh.airportrides.today">pittsburgh.airportrides.today</option>
<option value="http://plano.airportrides.today">plano.airportrides.today</option>
<option value="http://pomona.airportrides.today">pomona.airportrides.today</option>
<option value="http://portarthur.airportrides.today">portarthur.airportrides.today</option>
<option value="http://portland.airportrides.today">portland.airportrides.today</option>
<option value="http://portland.airportrides.today">portland.airportrides.today</option>
<option value="http://portsaintlucie.airportrides.today">portsaintlucie.airportrides.today</option>
<option value="http://providence.airportrides.today">providence.airportrides.today</option>
<option value="http://provo.airportrides.today">provo.airportrides.today</option>
<option value="http://pueblo.airportrides.today">pueblo.airportrides.today</option>
<option value="http://raleigh.airportrides.today">raleigh.airportrides.today</option>
<option value="http://ranchocucamonga.airportrides.today">ranchocucamonga.airportrides.today</option>
<option value="http://rapidcity.airportrides.today">rapidcity.airportrides.today</option>
<option value="http://redrock.airportrides.today">redrock.airportrides.today</option>
<option value="http://reno.airportrides.today">reno.airportrides.today</option>
<option value="http://rhodeisland.airportrides.today">rhodeisland.airportrides.today</option>
<option value="http://richmond.airportrides.today">richmond.airportrides.today</option>
<option value="http://richmond.airportrides.today">richmond.airportrides.today</option>
<option value="http://rio.airportrides.today">rio.airportrides.today</option>
<option value="http://riverside.airportrides.today">riverside.airportrides.today</option>
<option value="http://riverton.airportrides.today">riverton.airportrides.today</option>
<option value="http://rochester.airportrides.today">rochester.airportrides.today</option>
<option value="http://rockford.airportrides.today">rockford.airportrides.today</option>
<option value="http://rocksprings.airportrides.today">rocksprings.airportrides.today</option>
<option value="http://rollingwood.airportrides.today">rollingwood.airportrides.today</option>
<option value="http://roseville.airportrides.today">roseville.airportrides.today</option>
<option value="http://roundrock.airportrides.today">roundrock.airportrides.today</option>
<option value="http://rutland.airportrides.today">rutland.airportrides.today</option>
<option value="http://sacramento.airportrides.today">sacramento.airportrides.today</option>
<option value="http://saintpaul.airportrides.today">saintpaul.airportrides.today</option>
<option value="http://salem.airportrides.today">salem.airportrides.today</option>
<option value="http://salinas.airportrides.today">salinas.airportrides.today</option>
<option value="http://saltlakecity.airportrides.today">saltlakecity.airportrides.today</option>
<option value="http://samstown.airportrides.today">samstown.airportrides.today</option>
<option value="http://sanantonio.airportrides.today">sanantonio.airportrides.today</option>
<option value="http://sanbernardino.airportrides.today">sanbernardino.airportrides.today</option>
<option value="http://sanbuenaventura.airportrides.today">sanbuenaventura.airportrides.today</option>
<option value="http://sandiego.airportrides.today">sandiego.airportrides.today</option>
<option value="http://sandusky.airportrides.today">sandusky.airportrides.today</option>
<option value="http://sanfrancisco.airportrides.today">sanfrancisco.airportrides.today</option>
<option value="http://sanjose.airportrides.today">sanjose.airportrides.today</option>
<option value="http://santaana.airportrides.today">santaana.airportrides.today</option>
<option value="http://santaclara.airportrides.today">santaclara.airportrides.today</option>
<option value="http://santaclarita.airportrides.today">santaclarita.airportrides.today</option>
<option value="http://santafestation.airportrides.today">santafestation.airportrides.today</option>
<option value="http://santarosa.airportrides.today">santarosa.airportrides.today</option>
<option value="http://savannah.airportrides.today">savannah.airportrides.today</option>
<option value="http://scottsdale.airportrides.today">scottsdale.airportrides.today</option>
<option value="http://seasideheights.airportrides.today">seasideheights.airportrides.today</option>
<option value="http://seattle.airportrides.today">seattle.airportrides.today</option>
<option value="http://shakerheights.airportrides.today">shakerheights.airportrides.today</option>
<option value="http://shreveport.airportrides.today">shreveport.airportrides.today</option>
<option value="http://simivalley.airportrides.today">simivalley.airportrides.today</option>
<option value="http://siouxfalls.airportrides.today">siouxfalls.airportrides.today</option>
<option value="http://smyrna.airportrides.today">smyrna.airportrides.today</option>
<option value="http://solon.airportrides.today">solon.airportrides.today</option>
<option value="http://southbend.airportrides.today">southbend.airportrides.today</option>
<option value="http://southburlington.airportrides.today">southburlington.airportrides.today</option>
<option value="http://southcarolina.airportrides.today">southcarolina.airportrides.today</option>
<option value="http://southdakota.airportrides.today">southdakota.airportrides.today</option>
<option value="http://southingtontownship.airportrides.today">southingtontownship.airportrides.today</option>
<option value="http://southpoint.airportrides.today">southpoint.airportrides.today</option>
<option value="http://spokane.airportrides.today">spokane.airportrides.today</option>
<option value="http://springfield.airportrides.today">springfield.airportrides.today</option>
<option value="http://springfield.airportrides.today">springfield.airportrides.today</option>
<option value="http://springfield.airportrides.today">springfield.airportrides.today</option>
<option value="http://springvalley.airportrides.today">springvalley.airportrides.today</option>
<option value="http://stamford.airportrides.today">stamford.airportrides.today</option>
<option value="http://statenisland.airportrides.today">statenisland.airportrides.today</option>
<option value="http://stclair.airportrides.today">stclair.airportrides.today</option>
<option value="http://sterlingheights.airportrides.today">sterlingheights.airportrides.today</option>
<option value="http://stlouis.airportrides.today">stlouis.airportrides.today</option>
<option value="http://stockton.airportrides.today">stockton.airportrides.today</option>
<option value="http://stpetersburg.airportrides.today">stpetersburg.airportrides.today</option>
<option value="http://strongsville.airportrides.today">strongsville.airportrides.today</option>
<option value="http://summerlin.airportrides.today">summerlin.airportrides.today</option>
<option value="http://sunnyvale.airportrides.today">sunnyvale.airportrides.today</option>
<option value="http://sunsetstation.airportrides.today">sunsetstation.airportrides.today</option>
<option value="http://superior.airportrides.today">superior.airportrides.today</option>
<option value="http://surprise.airportrides.today">surprise.airportrides.today</option>
<option value="http://syracuse.airportrides.today">syracuse.airportrides.today</option>
<option value="http://tacoma.airportrides.today">tacoma.airportrides.today</option>
<option value="http://tallahassee.airportrides.today">tallahassee.airportrides.today</option>
<option value="http://tampa.airportrides.today">tampa.airportrides.today</option>
<option value="http://temecula.airportrides.today">temecula.airportrides.today</option>
<option value="http://tempe.airportrides.today">tempe.airportrides.today</option>
<option value="http://tennessee.airportrides.today">tennessee.airportrides.today</option>
<option value="http://texas.airportrides.today">texas.airportrides.today</option>
<option value="http://texasstation.airportrides.today">texasstation.airportrides.today</option>
<option value="http://thornton.airportrides.today">thornton.airportrides.today</option>
<option value="http://thousandoaks.airportrides.today">thousandoaks.airportrides.today</option>
<option value="http://toledo.airportrides.today">toledo.airportrides.today</option>
<option value="http://topeka.airportrides.today">topeka.airportrides.today</option>
<option value="http://torrance.airportrides.today">torrance.airportrides.today</option>
<option value="http://tucson.airportrides.today">tucson.airportrides.today</option>
<option value="http://tulsa.airportrides.today">tulsa.airportrides.today</option>
<option value="http://twinsburg.airportrides.today">twinsburg.airportrides.today</option>
<option value="http://utah.airportrides.today">utah.airportrides.today</option>
<option value="http://vallejo.airportrides.today">vallejo.airportrides.today</option>
<option value="http://vancouver.airportrides.today">vancouver.airportrides.today</option>
<option value="http://venetian.airportrides.today">venetian.airportrides.today</option>
<option value="http://vermont.airportrides.today">vermont.airportrides.today</option>
<option value="http://victorville.airportrides.today">victorville.airportrides.today</option>
<option value="http://virginia.airportrides.today">virginia.airportrides.today</option>
<option value="http://virginiabeach.airportrides.today">virginiabeach.airportrides.today</option>
<option value="http://visalia.airportrides.today">visalia.airportrides.today</option>
<option value="http://waco.airportrides.today">waco.airportrides.today</option>
<option value="http://warren.airportrides.today">warren.airportrides.today</option>
<option value="http://warren.airportrides.today">warren.airportrides.today</option>
<option value="http://warrensvilleheights.airportrides.today">warrensvilleheights.airportrides.today</option>
<option value="http://washington.airportrides.today">washington.airportrides.today</option>
<option value="http://washingtondc.airportrides.today">washingtondc.airportrides.today</option>
<option value="http://waterbury.airportrides.today">waterbury.airportrides.today</option>
<option value="http://watertown.airportrides.today">watertown.airportrides.today</option>
<option value="http://weirtonheights.airportrides.today">weirtonheights.airportrides.today</option>
<option value="http://westcovina.airportrides.today">westcovina.airportrides.today</option>
<option value="http://westgate.airportrides.today">westgate.airportrides.today</option>
<option value="http://westjordan.airportrides.today">westjordan.airportrides.today</option>
<option value="http://westlake.airportrides.today">westlake.airportrides.today</option>
<option value="http://westlakehills.airportrides.today">westlakehills.airportrides.today</option>
<option value="http://westminster.airportrides.today">westminster.airportrides.today</option>
<option value="http://westpalmbeach.airportrides.today">westpalmbeach.airportrides.today</option>
<option value="http://westvalleycity.airportrides.today">westvalleycity.airportrides.today</option>
<option value="http://westvirginia.airportrides.today">westvirginia.airportrides.today</option>
<option value="http://wheeling.airportrides.today">wheeling.airportrides.today</option>
<option value="http://wichita.airportrides.today">wichita.airportrides.today</option>
<option value="http://wichitafalls.airportrides.today">wichitafalls.airportrides.today</option>
<option value="http://williston.airportrides.today">williston.airportrides.today</option>
<option value="http://wilmington.airportrides.today">wilmington.airportrides.today</option>
<option value="http://wilmington.airportrides.today">wilmington.airportrides.today</option>
<option value="http://winstonsalem.airportrides.today">winstonsalem.airportrides.today</option>
<option value="http://wisconsin.airportrides.today">wisconsin.airportrides.today</option>
<option value="http://worcester.airportrides.today">worcester.airportrides.today</option>
<option value="http://wynn.airportrides.today">wynn.airportrides.today</option>
<option value="http://wyoming.airportrides.today">wyoming.airportrides.today</option>
<option value="http://xenia.airportrides.today">xenia.airportrides.today</option>
<option value="http://yellowsprings.airportrides.today">yellowsprings.airportrides.today</option>
<option value="http://yonkers.airportrides.today">yonkers.airportrides.today</option>
<option value="https://taxicabdelivery.online/2022/01105.php">springfield 01105</option>
<option value="https://taxicabdelivery.online/2022/01609.php">worcester 01609</option>
<option value="https://taxicabdelivery.online/2022/01854.php">lowell 01854</option>
<option value="https://taxicabdelivery.online/2022/02128.php">boston 02128</option>
<option value="https://taxicabdelivery.online/2022/02142.php">cambridge 02142</option>
<option value="https://taxicabdelivery.online/2022/02886.php">providence 02886</option>
<option value="https://taxicabdelivery.online/2022/03103.php">manchester 03103</option>
<option value="https://taxicabdelivery.online/2022/04102.php">portland 04102</option>
<option value="https://taxicabdelivery.online/2022/05403.php">burlington 05403</option>
<option value="https://taxicabdelivery.online/2022/05403.php">southburlington 05403</option>
<option value="https://taxicabdelivery.online/2022/05701.php">rutland 05701</option>
<option value="https://taxicabdelivery.online/2022/06103.php">hartford 06103</option>
<option value="https://taxicabdelivery.online/2022/06110.php">farmington 06110</option>
<option value="https://taxicabdelivery.online/2022/06497.php">bridgeport 06497</option>
<option value="https://taxicabdelivery.online/2022/06511.php">newhaven 06511</option>
<option value="https://taxicabdelivery.online/2022/06706.php">waterbury 06706</option>
<option value="https://taxicabdelivery.online/2022/06824.php">fairfield 06824</option>
<option value="https://taxicabdelivery.online/2022/06830.php">greenwich 06830</option>
<option value="https://taxicabdelivery.online/2022/06901.php">stamford 06901</option>
<option value="https://taxicabdelivery.online/2022/07102.php">newark 07102</option>
<option value="https://taxicabdelivery.online/2022/07114.php">elizabeth 07114</option>
<option value="https://taxicabdelivery.online/2022/07304.php">jerseycity 07304</option>
<option value="https://taxicabdelivery.online/2022/07503.php">peterson 07503</option>
<option value="https://taxicabdelivery.online/2022/08234.php">atlanticcity 08234</option>
<option value="https://taxicabdelivery.online/2022/08751.php">seasideheights 08751</option>
<option value="https://taxicabdelivery.online/2022/10036.php">manhattan 10036</option>
<option value="https://taxicabdelivery.online/2022/10039.php">nyc harlem 10039</option>
<option value="https://taxicabdelivery.online/2022/10306.php">statenisland 10306</option>
<option value="https://taxicabdelivery.online/2022/10460.php">bronx 10460</option>
<option value="https://taxicabdelivery.online/2022/10701.php">yonkers 10701</option>
<option value="https://taxicabdelivery.online/2022/11212.php">brownsville 11212</option>
<option value="https://taxicabdelivery.online/2022/11216.php">brooklyn 11216</option>
<option value="https://taxicabdelivery.online/2022/11430.php">newyorkcity 11430</option>
<option value="https://taxicabdelivery.online/2022/13208.php">syracuse 13208</option>
<option value="https://taxicabdelivery.online/2022/14204.php">buffalo 14204</option>
<option value="https://taxicabdelivery.online/2022/14607.php">rochester 14607</option>
<option value="https://taxicabdelivery.online/2022/15231.php">pittsburgh 15231</option>
<option value="https://taxicabdelivery.online/2022/18101.php">allentown 18101</option>
<option value="https://taxicabdelivery.online/2022/19153.php">philadelphia 19153</option>
<option value="https://taxicabdelivery.online/2022/19720.php">wilmington 19720</option>
<option value="https://taxicabdelivery.online/2022/19901.php">dover 19901</option>
<option value="https://taxicabdelivery.online/2022/20017.php">washingtondc 20017</option>
<option value="https://taxicabdelivery.online/2022/21240.php">baltimore 21240</option>
<option value="https://taxicabdelivery.online/2022/22202.php">dc 22202</option>
<option value="https://taxicabdelivery.online/2022/22203.php">arlington 22203</option>
<option value="https://taxicabdelivery.online/2022/22302.php">alexandria 22302</option>
<option value="https://taxicabdelivery.online/2022/22303.php">huntington 22303</option>
<option value="https://taxicabdelivery.online/2022/23220.php">richmond 23220</option>
<option value="https://taxicabdelivery.online/2022/23320.php">chesapeake 23320</option>
<option value="https://taxicabdelivery.online/2022/23453.php">virginiabeach 23453</option>
<option value="https://taxicabdelivery.online/2022/23518.php">norfolk 23518</option>
<option value="https://taxicabdelivery.online/2022/23602.php">newportnews 23602</option>
<option value="https://taxicabdelivery.online/2022/23666.php">hampton 23666</option>
<option value="https://taxicabdelivery.online/2022/25311.php">charleston 25311</option>
<option value="https://taxicabdelivery.online/2022/25704.php">huntington 25704</option>
<option value="https://taxicabdelivery.online/2022/26003.php">wheeling 26003</option>
<option value="https://taxicabdelivery.online/2022/26101.php">parkersburg 26101</option>
<option value="https://taxicabdelivery.online/2022/26505.php">morgantown 26505</option>
<option value="https://taxicabdelivery.online/2022/27104.php">winstonsalem 27104</option>
<option value="https://taxicabdelivery.online/2022/27262.php">highpoint 27262</option>
<option value="https://taxicabdelivery.online/2022/27406.php">greensboro 27406</option>
<option value="https://taxicabdelivery.online/2022/27511.php">cary 27511</option>
<option value="https://taxicabdelivery.online/2022/27560.php">durham 27560</option>
<option value="https://taxicabdelivery.online/2022/27560.php">raleigh 27560</option>
<option value="https://taxicabdelivery.online/2022/28208.php">charlotte 28208</option>
<option value="https://taxicabdelivery.online/2022/28314.php">fayetteville 28314</option>
<option value="https://taxicabdelivery.online/2022/28405.php">wilmington 28405</option>
<option value="https://taxicabdelivery.online/2022/29170.php">columbia 29170</option>
<option value="https://taxicabdelivery.online/2022/29418.php">charleston 29418</option>
<option value="https://taxicabdelivery.online/2022/30320.php">atlanta 30320</option>
<option value="https://taxicabdelivery.online/2022/30602.php">athens 30602</option>
<option value="https://taxicabdelivery.online/2022/30906.php">augusta 30906</option>
<option value="https://taxicabdelivery.online/2022/31408.php">savannah 31408</option>
<option value="https://taxicabdelivery.online/2022/32218.php">jacksonville 32218</option>
<option value="https://taxicabdelivery.online/2022/32310.php">tallahassee 32310</option>
<option value="https://taxicabdelivery.online/2022/32609.php">gainesville 32609</option>
<option value="https://taxicabdelivery.online/2022/32827.php">orlando 32827</option>
<option value="https://taxicabdelivery.online/2022/32901.php">palmbay 32901</option>
<option value="https://taxicabdelivery.online/2022/33009.php">hollywood 33009</option>
<option value="https://taxicabdelivery.online/2022/33013.php">hialeah 33013</option>
<option value="https://taxicabdelivery.online/2022/33023.php">pembrokepines 33023</option>
<option value="https://taxicabdelivery.online/2022/33025.php">miramar 33025</option>
<option value="https://taxicabdelivery.online/2022/33056.php">miamigardens 33056</option>
<option value="https://taxicabdelivery.online/2022/33071.php">coralsprings 33071</option>
<option value="https://taxicabdelivery.online/2022/33126.php">miami 33126</option>
<option value="https://taxicabdelivery.online/2022/33315.php">fortlauderdale 33315</option>
<option value="https://taxicabdelivery.online/2022/33406.php">westpalmbeach 33406</option>
<option value="https://taxicabdelivery.online/2022/33432.php">boca 33432
<option value="https://taxicabdelivery.online/2022/33607.php">tampa 33607</option>
<option value="https://taxicabdelivery.online/2022/33713.php">stpetersburg 33713</option>
<option value="https://taxicabdelivery.online/2022/33762.php">clearwater 33762</option>
<option value="https://taxicabdelivery.online/2022/33913.php">capecoral 33913
<option value="https://taxicabdelivery.online/2022/34957.php">portsaintlucie 34957</option>
<option value="https://taxicabdelivery.online/2022/35212.php"><b>alabama</b></option>
<option value="https://taxicabdelivery.online/2022/35212.php">birmingham 35212</option>
<option value="https://taxicabdelivery.online/2022/35824.php">huntsville 35824</option>
<option value="https://taxicabdelivery.online/2022/36043.php">montgomery 36043</option>
<option value="https://taxicabdelivery.online/2022/36605.php">mobile 36605</option>
<option value="https://taxicabdelivery.online/2022/37027.php">brentwood 37027</option>
<option value="https://taxicabdelivery.online/2022/37040.php">clarksville 37040</option>
<option value="https://taxicabdelivery.online/2022/37064.php">franklin 37064</option>
<option value="https://taxicabdelivery.online/2022/37066.php">gallatin 37066</option>
<option value="https://taxicabdelivery.online/2022/37076.php">hermitage 37076</option>
<option value="https://taxicabdelivery.online/2022/37087.php">lebanon 37087</option>
<option value="https://taxicabdelivery.online/2022/37122.php">mountjuliet 37122</option>
<option value="https://taxicabdelivery.online/2022/37129.php">murfreesboro 37129</option>
<option value="https://taxicabdelivery.online/2022/37138.php">oldhickory 37138</option>
<option value="https://taxicabdelivery.online/2022/37167.php">smyrna 37167</option>
<option value="https://taxicabdelivery.online/2022/37214.php">donelson 37214</option>
<option value="https://taxicabdelivery.online/2022/37214.php">nashville 37214</option>
<option value="https://taxicabdelivery.online/2022/37402.php">chattanooga 37402</option>
<option value="https://taxicabdelivery.online/2022/37914.php">knoxville 37914</option>
<option value="https://taxicabdelivery.online/2022/38114.php">orangemound 38114</option>
<option value="https://taxicabdelivery.online/2022/38116.php">memphis 38116</option>
<option value="https://taxicabdelivery.online/2022/38305.php">jackson 38305</option>
<option value="https://taxicabdelivery.online/2022/38340.php">henderson 38340</option>
<option value="https://taxicabdelivery.online/2022/39208.php">jackson 39208</option>
<option value="https://taxicabdelivery.online/2022/40209.php">louisville 40209</option>
<option value="https://taxicabdelivery.online/2022/41048.php">cincinnati 41048</option>
<option value="https://taxicabdelivery.online/2022/43219.php">columbus 43219</option>
<option value="https://taxicabdelivery.online/2022/43402.php">bowlinggreen 43402</option>
<option value="https://taxicabdelivery.online/2022/43605.php">toledo 43605</option>
<option value="https://taxicabdelivery.online/2022/43610.php">glenwood 43610</option>
<option value="https://taxicabdelivery.online/2022/44017.php">berea 44017</option>
<option value="https://taxicabdelivery.online/2022/44035.php">elyria 44035</option>
<option value="https://taxicabdelivery.online/2022/44039.php">northridgeville 44039</option>
<option value="https://taxicabdelivery.online/2022/44053.php">lorain 44053</option>
<option value="https://taxicabdelivery.online/2022/44060.php">mentor 44060</option>
<option value="https://taxicabdelivery.online/2022/44070.php">northolmsted 44070</option>
<option value="https://taxicabdelivery.online/2022/44087.php">twinsburg 44087</option>
<option value="https://taxicabdelivery.online/2022/44103.php">superior 44103</option>
<option value="https://taxicabdelivery.online/2022/44105.php">kinsman 44105</option>
<option value="https://taxicabdelivery.online/2022/44107.php">lakewood 44107</option>
<option value="https://taxicabdelivery.online/2022/44108.php">st clair 44108</option>
<option value="https://taxicabdelivery.online/2022/44112.php">eastcleveland 44112</option>
<option value="https://taxicabdelivery.online/2022/44114.php">cleveland 44114</option>
<option value="https://taxicabdelivery.online/2022/44121.php">clevelandheights 44121</option>
<option value="https://taxicabdelivery.online/2022/44122.php">shakerheights 44122</option>
<option value="https://taxicabdelivery.online/2022/44123.php">euclid 44123</option>
<option value="https://taxicabdelivery.online/2022/44125.php">garfieldheights 44125</option>
<option value="https://taxicabdelivery.online/2022/44128.php">harvard 44128</option>
<option value="https://taxicabdelivery.online/2022/44128.php">miles 44128</option>
<option value="https://taxicabdelivery.online/2022/44128.php">warrensvilleheights 44128</option>
<option value="https://taxicabdelivery.online/2022/44130.php">middleburgheights 44130</option>
<option value="https://taxicabdelivery.online/2022/44131.php">independence 44131</option>
<option value="https://taxicabdelivery.online/2022/44133.php">northroyalton 44133</option>
<option value="https://taxicabdelivery.online/2022/44136.php">strongsville 44136</option>
<option value="https://taxicabdelivery.online/2022/44137.php">mapleheights 44137</option>
<option value="https://taxicabdelivery.online/2022/44139.php">solon 44139</option>
<option value="https://taxicabdelivery.online/2022/44142.php">brookpark 44142</option>
<option value="https://taxicabdelivery.online/2022/44145.php">westlake 44145</option>
<option value="https://taxicabdelivery.online/2022/44146.php">bedford 44146</option>
<option value="https://taxicabdelivery.online/2022/44219.php">parma 44129</option>
<option value="https://taxicabdelivery.online/2022/44221.php">cuyahogafalls 44221</option>
<option value="https://taxicabdelivery.online/2022/44240.php">kent 44240</option>
<option value="https://taxicabdelivery.online/2022/44256.php">medina 44256</option>
<option value="https://taxicabdelivery.online/2022/44307.php">akron 44307</option>
<option value="https://taxicabdelivery.online/2022/44470.php">southingtontownship 44470</option>
<option value="https://taxicabdelivery.online/2022/44483.php">warren 44483</option>
<option value="https://taxicabdelivery.online/2022/44706.php">canton 44706</option>
<option value="https://taxicabdelivery.online/2022/44870.php">erie 44870</option>
<option value="https://taxicabdelivery.online/2022/44870.php">sandusky 44870</option>
<option value="https://taxicabdelivery.online/2022/45011.php">hamilton 45011</option>
<option value="https://taxicabdelivery.online/2022/45385.php">xenia 45385</option>
<option value="https://taxicabdelivery.online/2022/45469.php">dayton 45469</option>
<option value="https://taxicabdelivery.online/2022/45837.php">yellowsprings 45837</option>
<option value="https://taxicabdelivery.online/2022/46241.php">indianapolis 46241</option>
<option value="https://taxicabdelivery.online/2022/46637.php">southbend 46637</option>
<option value="https://taxicabdelivery.online/2022/46802.php">fortwayne 46802</option>
<option value="https://taxicabdelivery.online/2022/47708.php">evansville 47708</option>
<option value="https://taxicabdelivery.online/2022/48093.php">warren 48093</option>
<option value="https://taxicabdelivery.online/2022/48104.php">annarbor 48104</option>
<option value="https://taxicabdelivery.online/2022/48174.php">detroit 48174</option>
<option value="https://taxicabdelivery.online/2022/48310.php">sterlingheights 48310</option>
<option value="https://taxicabdelivery.online/2022/48502.php">flint 48502</option>
<option value="https://taxicabdelivery.online/2022/48823.php">lansing 48823</option>
<option value="https://taxicabdelivery.online/2022/49503.php">grandrapids 49503</option>
<option value="https://taxicabdelivery.online/2022/50321.php">desmoines 50321</option>
<option value="https://taxicabdelivery.online/2022/52404.php">cedarrapids 52404</option>
<option value="https://taxicabdelivery.online/2022/53207.php">milwaukee 53207</option>
<option value="https://taxicabdelivery.online/2022/53207.php">westjordan</option>
<option value="https://taxicabdelivery.online/2022/53704.php">madison 53704</option>
<option value="https://taxicabdelivery.online/2022/54313.php">greenbay 54313</option>
<option value="https://taxicabdelivery.online/2022/55317.php">paisley park 55317</option>
<option value="https://taxicabdelivery.online/2022/55450.php">minneapolis 55450</option>
<option value="https://taxicabdelivery.online/2022/55450.php">saintpaul 55450</option>
<option value="https://taxicabdelivery.online/2022/57006.php">brookings 57006</option>
<option value="https://taxicabdelivery.online/2022/57106.php">siouxfalls 57106</option>
<option value="https://taxicabdelivery.online/2022/57201.php">watertown 57201</option>
<option value="https://taxicabdelivery.online/2022/57401.php">aberdeen 57401</option>
<option value="https://taxicabdelivery.online/2022/57701.php">rapidcity 57701</option>
<option value="https://taxicabdelivery.online/2022/58102.php">fargo 58102</option>
<option value="https://taxicabdelivery.online/2022/58801.php">williston 58801</option>
<option value="https://taxicabdelivery.online/2022/59105.php">billings 59105</option>
<option value="https://taxicabdelivery.online/2022/60120.php">elgin 60120</option>
<option value="https://taxicabdelivery.online/2022/60432.php">joliet 60432</option>
<option value="https://taxicabdelivery.online/2022/60506.php">aurora 60506</option>
<option value="https://taxicabdelivery.online/2022/60540.php">naperville 60540</option>
<option value="https://taxicabdelivery.online/2022/60666.php">chicago 60666</option>
<option value="https://taxicabdelivery.online/2022/61109.php">rockford 61109</option>
<option value="https://taxicabdelivery.online/2022/61604.php">peoria 61604</option>
<option value="https://taxicabdelivery.online/2022/62701.php">springfield 62701</option>
<option value="https://taxicabdelivery.online/2022/63145.php">stlouis 63145</option>
<option value="https://taxicabdelivery.online/2022/64153.php">kansascity 64153</option>
<option value="https://taxicabdelivery.online/2022/64153.php">kansascity 64153</option>
<option value="https://taxicabdelivery.online/2022/65201.php">columbia 65201</option>
<option value="https://taxicabdelivery.online/2022/65897.php">springfield 65897</option>
<option value="https://taxicabdelivery.online/2022/66062.php">olathe 66062</option>
<option value="https://taxicabdelivery.online/2022/66214.php">overlandpark 66214</option>
<option value="https://taxicabdelivery.online/2022/66619.php">topeka 66619</option>
<option value="https://taxicabdelivery.online/2022/67209.php">wichita 67209</option>
<option value="https://taxicabdelivery.online/2022/68110.php">omaha 68110</option>
<option value="https://taxicabdelivery.online/2022/70062.php">neworleans 70062</option>
<option value="https://taxicabdelivery.online/2022/70504.php">lafayette 70504</option>
<option value="https://taxicabdelivery.online/2022/70811.php">batonrouge 70811</option>
<option value="https://taxicabdelivery.online/2022/71109.php">shreveport 71109</option>
<option value="https://taxicabdelivery.online/2022/72202.php">littlerock 72202</option>
<option value="https://taxicabdelivery.online/2022/73069.php">norman 73069</option>
<option value="https://taxicabdelivery.online/2022/73159.php">oklahomacity 73159</option>
<option value="https://taxicabdelivery.online/2022/74115.php">tulsa 74115</option>
<option value="https://taxicabdelivery.online/2022/75006.php">carrollton 75006</option>
<option value="https://taxicabdelivery.online/2022/75034.php">frisco 75034</option>
<option value="https://taxicabdelivery.online/2022/75040.php">garland 75040</option>
<option value="https://taxicabdelivery.online/2022/75052.php">grandprairie 75052</option>
<option value="https://taxicabdelivery.online/2022/75062.php">irving 75062</option>
<option value="https://taxicabdelivery.online/2022/75070.php">mckinney 75070</option>
<option value="https://taxicabdelivery.online/2022/75074.php">plano 75074</option>
<option value="https://taxicabdelivery.online/2022/75149.php">mesquite 75149</option>
<option value="https://taxicabdelivery.online/2022/75261.php">dallas 75261</option>
<option value="https://taxicabdelivery.online/2022/76015.php">arlington 76015</option>
<option value="https://taxicabdelivery.online/2022/76102.php">fortworth 76102</option>
<option value="https://taxicabdelivery.online/2022/76205.php">denton 76205</option>
<option value="https://taxicabdelivery.online/2022/76308.php">wichitafalls 76308</option>
<option value="https://taxicabdelivery.online/2022/76542.php">killeen 76542</option>
<option value="https://taxicabdelivery.online/2022/77032.php">houston 77032</option>
<option value="https://taxicabdelivery.online/2022/77640.php">portarthur 77640</option>
<option value="https://taxicabdelivery.online/2022/77701.php">beaumont 77701</option>
<option value="https://taxicabdelivery.online/2022/78041.php">laredo 78041</option>
<option value="https://taxicabdelivery.online/2022/78216.php">sanantonio 78216</option>
<option value="https://taxicabdelivery.online/2022/78415.php">corpuschristi 78415</option>
<option value="https://taxicabdelivery.online/2022/78501.php">mcallen 78501</option>
<option value="https://taxicabdelivery.online/2022/78610.php">buda 78610</option>
<option value="https://taxicabdelivery.online/2022/78613.php">cedarpark 78613</option>
<option value="https://taxicabdelivery.online/2022/78624.php">fredericksburg 78624</option>
<option value="https://taxicabdelivery.online/2022/78641.php">leander 78641</option>
<option value="https://taxicabdelivery.online/2022/78659.php">hills 78659</option>
<option value="https://taxicabdelivery.online/2022/78660.php">pflugerville 78660</option>
<option value="https://taxicabdelivery.online/2022/78665.php">roundrock 78665</option>
<option value="https://taxicabdelivery.online/2022/78710.php">waco 78710</option>
<option value="https://taxicabdelivery.online/2022/78719.php">austin 78719</option>
<option value="https://taxicabdelivery.online/2022/78734.php">lakeway 78734</option>
<option value="https://taxicabdelivery.online/2022/78738.php">beecave 78738</option>
<option value="https://taxicabdelivery.online/2022/78746.php">rollingwood 78746</option>
<option value="https://taxicabdelivery.online/2022/78746.php">westlakehills 78746</option>
<option value="https://taxicabdelivery.online/2022/79101.php">amarillo 79101</option>
<option value="https://taxicabdelivery.online/2022/79401.php">lubbock 79401</option>
<option value="https://taxicabdelivery.online/2022/79601.php">abilene 79601</option>
<option value="https://taxicabdelivery.online/2022/79705.php">midland 79705</option>
<option value="https://taxicabdelivery.online/2022/79925.php">elpaso 79925</option>
<option value="https://taxicabdelivery.online/2022/80002.php">arvada 80002</option>
<option value="https://taxicabdelivery.online/2022/80104.php">castlerock 80104</option>
<option value="https://taxicabdelivery.online/2022/80112.php">centennial 80112</option>
<option value="https://taxicabdelivery.online/2022/80112.php">dtc 80112</option>
<option value="https://taxicabdelivery.online/2022/80112.php">englewood 80112</option>
<option value="https://taxicabdelivery.online/2022/80115.php">aurora 80015</option>
<option value="https://taxicabdelivery.online/2022/80128.php">littleton 80128</option>
<option value="https://taxicabdelivery.online/2022/80134.php">parker 80134</option>
<option value="https://taxicabdelivery.online/2022/80202.php">denver 80202</option>
<option value="https://taxicabdelivery.online/2022/80227.php">lakewood 80227</option>
<option value="https://taxicabdelivery.online/2022/80233.php">thornton 80233</option>
<option value="https://taxicabdelivery.online/2022/80304.php">boulder 80304</option>
<option value="https://taxicabdelivery.online/2022/80333.php">westminster 80333</option>
<option value="https://taxicabdelivery.online/2022/80401.php">golden 80401</option>
<option value="https://taxicabdelivery.online/2022/80422.php">blackhawk 80422</option>
<option value="https://taxicabdelivery.online/2022/80424.php">brecknridge 80424</option>
<option value="https://taxicabdelivery.online/2022/80501.php">longmont 80501</option>
<option value="https://taxicabdelivery.online/2022/80524.php">fortcollins 80524</option>
<option value="https://taxicabdelivery.online/2022/80906.php">coloradosprings 80906</option>
<option value="https://taxicabdelivery.online/2022/80921.php">monument 80921</option>
<option value="https://taxicabdelivery.online/2022/81001.php">pueblo 81001</option>
<option value="https://taxicabdelivery.online/2022/81611.php">aspen 81611</option>
<option value="https://taxicabdelivery.online/2022/82001.php">cheyenne 82001</option>
<option value="https://taxicabdelivery.online/2022/82072.php">laramie 82072</option>
<option value="https://taxicabdelivery.online/2022/82414.php">cody 82414</option>
<option value="https://taxicabdelivery.online/2022/82501.php">riverton 82501</option>
<option value="https://taxicabdelivery.online/2022/82604.php">casper 82604</option>
<option value="https://taxicabdelivery.online/2022/82718.php">gillette 82718</option>
<option value="https://taxicabdelivery.online/2022/82901.php">rocksprings 82901</option>
<option value="https://taxicabdelivery.online/2022/82930.php">evanston 82930</option>
<option value="https://taxicabdelivery.online/2022/82935.php">greenriver 82935</option>
<option value="https://taxicabdelivery.online/2022/83002.php">jacksonhole 83002</option>
<option value="https://taxicabdelivery.online/2022/83705.php">boise 83705</option>
<option value="https://taxicabdelivery.online/2022/84122.php">saltlakecity 84122</option>
<option value="https://taxicabdelivery.online/2022/84128.php">westvalleycity 84128</option>
<option value="https://taxicabdelivery.online/2022/84602.php">provo 84602</option>
<option value="https://taxicabdelivery.online/2022/85034.php">phoenix 85034</option>
<option value="https://taxicabdelivery.online/2022/85212.php">mesa 85212</option>
<option value="https://taxicabdelivery.online/2022/85233.php">gilbert 85233</option>
<option value="https://taxicabdelivery.online/2022/85249.php">chandler 85249</option>
<option value="https://taxicabdelivery.online/2022/85260.php">scottsdale 85260</option>
<option value="https://taxicabdelivery.online/2022/85282.php">tempe 85282</option>
<option value="https://taxicabdelivery.online/2022/85307.php">glendale 85307</option>
<option value="https://taxicabdelivery.online/2022/85379.php">surprise 85379</option>
<option value="https://taxicabdelivery.online/2022/85383.php">peoria 85383</option>
<option value="https://taxicabdelivery.online/2022/85756.php">tucson 85756</option>
<option value="https://taxicabdelivery.online/2022/87106.php">albuquerque 87106</option>
<option value="https://taxicabdelivery.online/2022/89014.php">sunsetstation 89014</option>
<option value="https://taxicabdelivery.online/2022/89029.php">laughlin 89029</option>
<option value="https://taxicabdelivery.online/2022/89030.php">northlasvegas 89030</option>
<option value="https://taxicabdelivery.online/2022/89052.php">greenvalleyranch 89052</option>
<option value="https://taxicabdelivery.online/2022/89052.php">henderson 89052</option>
<option value="https://taxicabdelivery.online/2022/89060.php">pahrump 89060</option>
<option value="https://taxicabdelivery.online/2022/89084.php">alliante 89084</option>
<option value="https://taxicabdelivery.online/2022/89119.php">aria 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">bellagio 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">caesarspalace 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">circuscircus 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">encore 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">lasvegas 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">luxor 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">mandalaybay 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">mgmgrand 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">montecarlo 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">orleans 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">ovo 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">palazzo 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">palms 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">rio 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">texasstation 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">venetian 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">westgate 89119</option>
<option value="https://taxicabdelivery.online/2022/89119.php">wynn 89119</option>
<option value="https://taxicabdelivery.online/2022/89122.php">samstown 89122</option>
<option value="https://taxicabdelivery.online/2022/89130.php">santafestation 89130</option>
<option value="https://taxicabdelivery.online/2022/89135.php">redrock 89135</option>
<option value="https://taxicabdelivery.online/2022/89135.php">summerlin 89135</option>
<option value="https://taxicabdelivery.online/2022/89148.php">springvalley 89148</option>
<option value="https://taxicabdelivery.online/2022/89183.php">southpoint 89183</option>
<option value="https://taxicabdelivery.online/2022/89511.php">reno 89511</option>
<option value="https://taxicabdelivery.online/2022/89706.php">carsoncity 89706</option>
<option value="https://taxicabdelivery.online/2022/90043.php">losangeles 90043</option>
<option value="https://taxicabdelivery.online/2022/90115.php">la 90015</option>
<option value="https://taxicabdelivery.online/2022/90241.php">downey 90241</option>
<option value="https://taxicabdelivery.online/2022/90301.php">inglewood 90301</option>
<option value="https://taxicabdelivery.online/2022/90505.php">torrance 90505</option>
<option value="https://taxicabdelivery.online/2022/90650.php">norwalk 90650</option>
<option value="https://taxicabdelivery.online/2022/90840.php">longbeach 90840</option>
<option value="https://taxicabdelivery.online/2022/91105.php">pasadena 91105</option>
<option value="https://taxicabdelivery.online/2022/91210.php">glendale 91210</option>
<option value="https://taxicabdelivery.online/2022/91321.php">santaclarita 91321</option>
<option value="https://taxicabdelivery.online/2022/91360.php">thousandoaks 91360</option>
<option value="https://taxicabdelivery.online/2022/91505.php">burbank 91505</option>
<option value="https://taxicabdelivery.online/2022/91731.php">elmonte 91731</option>
<option value="https://taxicabdelivery.online/2022/91739.php">ranchocucamonga 91739</option>
<option value="https://taxicabdelivery.online/2022/91763.php">pomona 91763</option>
<option value="https://taxicabdelivery.online/2022/91764.php">ontario 91764</option>
<option value="https://taxicabdelivery.online/2022/91792.php">westcovina 91792</option>
<option value="https://taxicabdelivery.online/2022/91909.php">chulavista 91909</option>
<option value="https://taxicabdelivery.online/2022/92008.php">oceanside 92008</option>
<option value="https://taxicabdelivery.online/2022/92025.php">escondido 92025</option>
<option value="https://taxicabdelivery.online/2022/92101.php">sandiego 92101</option>
<option value="https://taxicabdelivery.online/2022/92335.php">fontana 92335</option>
<option value="https://taxicabdelivery.online/2022/92394.php">victorville 92394</option>
<option value="https://taxicabdelivery.online/2022/92405.php">sanbernardino 92405</option>
<option value="https://taxicabdelivery.online/2022/92521.php">riverside 92521</option>
<option value="https://taxicabdelivery.online/2022/92562.php">murrieta 92562</option>
<option value="https://taxicabdelivery.online/2022/92571.php">morenovalley 92571</option>
<option value="https://taxicabdelivery.online/2022/92592.php">temecula 92592</option>
<option value="https://taxicabdelivery.online/2022/92618.php">irvine 92618</option>
<option value="https://taxicabdelivery.online/2022/92626.php">costamesa 92626</option>
<option value="https://taxicabdelivery.online/2022/92646.php">huntingtonbeach 92646</option>
<option value="https://taxicabdelivery.online/2022/92707.php">anaheim 92707</option>
<option value="https://taxicabdelivery.online/2022/92707.php">santaana 92707</option>
<option value="https://taxicabdelivery.online/2022/92833.php">fullerton 92833</option>
<option value="https://taxicabdelivery.online/2022/92840.php">gardengrove 92840</option>
<option value="https://taxicabdelivery.online/2022/92865.php">orange 92865</option>
<option value="https://taxicabdelivery.online/2022/92879.php">corona 92879</option>
<option value="https://taxicabdelivery.online/2022/93003.php">sanbuenaventura 93003</option>
<option value="https://taxicabdelivery.online/2022/93036.php">oxnard 93036</option>
<option value="https://taxicabdelivery.online/2022/93065.php">simivalley 93065</option>
<option value="https://taxicabdelivery.online/2022/93277.php">visalia 93277</option>
<option value="https://taxicabdelivery.online/2022/93311.php">bakersfield 93311</option>
<option value="https://taxicabdelivery.online/2022/93550.php">lancaster 93550</option>
<option value="https://taxicabdelivery.online/2022/93550.php">palmdale 93550</option>
<option value="https://taxicabdelivery.online/2022/93727.php">fresno 93727</option>
<option value="https://taxicabdelivery.online/2022/93905.php">salinas 93905</option>
<option value="https://taxicabdelivery.online/2022/94015.php">dalycity 94015</option>
<option value="https://taxicabdelivery.online/2022/94086.php">sunnyvale 94086</option>
<option value="https://taxicabdelivery.online/2022/94218.php">sanfrancisco 94218</option>
<option value="https://taxicabdelivery.online/2022/94509.php">antioch 94509</option>
<option value="https://taxicabdelivery.online/2022/94520.php">concord 94520</option>
<option value="https://taxicabdelivery.online/2022/94538.php">fremont 94538</option>
<option value="https://taxicabdelivery.online/2022/94542.php">hayward 94542</option>
<option value="https://taxicabdelivery.online/2022/94591.php">vallejo 94591</option>
<option value="https://taxicabdelivery.online/2022/94621.php">oakland 94621</option>
<option value="https://taxicabdelivery.online/2022/94720.php">berkeley 94720</option>
<option value="https://taxicabdelivery.online/2022/94801.php">richmond 94801</option>
<option value="https://taxicabdelivery.online/2022/95110.php">sanjose 95110</option>
<option value="https://taxicabdelivery.online/2022/95110.php">santaclara 95110</option>
<option value="https://taxicabdelivery.online/2022/95204.php">stockton 95204</option>
<option value="https://taxicabdelivery.online/2022/95354.php">modesto 95354</option>
<option value="https://taxicabdelivery.online/2022/95404.php">santarosa 95404</option>
<option value="https://taxicabdelivery.online/2022/95661.php">roseville 95661</option>
<option value="https://taxicabdelivery.online/2022/95758.php">elkgrove 95758</option>
<option value="https://taxicabdelivery.online/2022/95837.php">sacramento 95837</option>
<option value="https://taxicabdelivery.online/2022/96770.php">molokai 96770</option>
<option value="https://taxicabdelivery.online/2022/96815.php">honolulu 96815</option>
<option value="https://taxicabdelivery.online/2022/96819.php">hawaii 96819</option>
<option value="https://taxicabdelivery.online/2022/97030.php">gresham 97030</option>
<option value="https://taxicabdelivery.online/2022/97218.php">portland 97218</option>
<option value="https://taxicabdelivery.online/2022/97301.php">salem 97301</option>
<option value="https://taxicabdelivery.online/2022/97402.php">eugene 97402</option>
<option value="https://taxicabdelivery.online/2022/98005.php">bellevue 98005</option>
<option value="https://taxicabdelivery.online/2022/98158.php">seattle 98158</option>
<option value="https://taxicabdelivery.online/2022/98158.php">tacoma 98158</option>
<option value="https://taxicabdelivery.online/2022/98203.php">everett 98203</option>
<option value="https://taxicabdelivery.online/2022/98664.php">vancouver 98664</option>
<option value="https://taxicabdelivery.online/2022/99202.php">spokane 99202</option>
<option value="https://taxicabdelivery.online/2022/99519.php">anchorage 99519</option>
<option value="https://taxicabdelivery.online/2022/united/menus/index.php">ALL USA Menus</option>
<option value="https://taxicabdelivery.online/2022/">yourcityhere</option>

</select>
</p>
</div>
<header class="container header-section d-flex justify-content-center mt-2">
<img src="./image/airportrides.today_at_gmail.jpg" height="270" width="100%"/>
</header>
<div class="container">
  <div class="main-form col-md-12 col-lg-12 col-sm-12">
  <div class="row">
    <div class="col-md-12">
      <h2>Universal Taxi Cab, Delivery, & Errand Calculator USA - Independent Contractors</h2>
    </div>
  </div>
  <div class="row">
  <form action="" method="post" id="form" class="col-md-12 col-lg-12 col-sm-12" onsubmit="return validateForm(event)">
    <div class="form-group">
      <label>I need a ride or delivery</label>
      <select name="rideType" id="rideType" class="form-control">
                                        <option id="item40_0_option" selected="" value="Now ASAP 15 minutes or less">
                                            Now ASAP 15 minutes or less
                                        </option>
                                        <option id="item40_1_option" value="DELIVERY Now">
                                            Delivery now
                                        </option>
                                        <option id="item40_2_option" value="Schedule a RIDE">
                                            Schedule a ride
                                        </option>
                                        <option id="item40_3_option" value="Schedule Delivery">
                                            Schedule delivery
                                        </option>
                                        <option id="item40_4_option" value="Save Ride for future">
                                            Save ride for future
                                        </option>
                                        <option id="item40_5_option" value="Save Delivery for Future">
                                            Save delivery for future
                                        </option>
                                    </select>
    </div>

    <div class="form-group">
      <div class="row">
        <div class="col-md-6">
          <label>Select a date</label>
          <input type="date" class="form-control" name="dateTime" id="dateTime" value="<?php echo date("Y-m-d"); ?>">
        </div>
        <div class="col-md-2">
          <label>Hour</label>
          <select name="timeHours" id="timeHours" class="form-control">                                        
                                        <option value="1">
                                            1
                                        </option>
                                        <option value="2">
                                            2
                                        </option>
                                        <option value="3">
                                            3
                                        </option>
                                        <option value="4">
                                            4
                                        </option>
                                        <option value="5">
                                            5
                                        </option>
                                        <option selected value="6">
                                            6
                                        </option>
                                        <option value="7">
                                            7
                                        </option>
                                        <option value="8">
                                            8
                                        </option>
                                        <option value="9">
                                            9
                                        </option>
                                        <option value="10">
                                            10
                                        </option>
                                        <option value="11">
                                            11
                                        </option>
                                        <option value="12">
                                            12
                                        </option>
                                    </select>
        </div>
        <div class="col-md-2">
          <label>Minutes</label>
          <select name="timeMinutes" id="timeMinutes" class="form-control">                                
                                        <option value="00">
                                            00
                                        </option>
                                        <option selected="" value="15">
                                            15
                                        </option>
                                        <option value="30">
                                            30
                                        </option>
                                        <option value="45">
                                            45
                                        </option>
                                    </select>
        </div>
        <div class="col-md-2">
          <label>AM/PM</label>
          <select name="timeFormat" id="timeFormat" class="form-control">                                        
                                        <option selected="" value="AM">
                                            AM
                                        </option>
                                        <option value="PM">
                                            PM
                                        </option>
                                    </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>In or near</label>
      <input type="text" list="majorLocationList" class="form-control" name="majorLocation" value="Denver" id="majorLocation">
      <datalist id="majorLocationList">
        <option id="item24_0_option" value="Akron Canton">
        Akron Canton
    </option>
        <option id="item24_1_option" value="Alabama">
        Alabama
    </option>
        <option id="item24_2_option" value="Alaska">
        Alaska
    </option>
        <option id="item24_3_option" value="Albuquerque">
        Albuquerque
    </option>
        <option id="item24_4_option" value="Alexandria">
        Alexandria
    </option>
        <option id="item24_5_option" value="Allentwon">
        Allentwon
    </option>
        <option id="item24_6_option" value="Amarillo">
        Amarillo
    </option>
        <option id="item24_7_option" value="Anaheim">
        Anaheim
    </option>
        <option id="item24_8_option" value="Anchorage">
        Anchorage
    </option>
        <option id="item24_9_option" value="Ann Arbor">
        Ann Arbor
    </option>
        <option id="item24_10_option" value="Antioch">
        Antioch
    </option>
        <option id="item24_11_option" value="Arizona">
        Arizona
    </option>
        <option id="item24_12_option" value="Arkansas">
        Arkansas
    </option>
        <option id="item24_13_option" value="Arlington">
        Arlington
    </option>
        <option id="item24_14_option" value="Arvada">
        Arvada
    </option>
        <option id="item24_15_option" value="Athens">
        Athens
    </option>
        <option id="item24_16_option" value="Atlanta">
        Atlanta
    </option>
        <option id="item24_17_option" value="Atlantic City">
        Atlantic City
    </option>
        <option id="item24_18_option" value="Augusta">
        Augusta
    </option>
        <option id="item24_19_option" value="Aurora">
        Aurora
    </option>
        <option id="item24_20_option" value="Austin">
        Austin
    </option>
        <option id="item24_21_option" value="Bakersfield">
        Bakersfield
    </option>
        <option id="item24_22_option" value="Baltimore">
        Baltimore
    </option>
        <option id="item24_23_option" value="Baton Rouge">
        Baton Rouge
    </option>
        <option id="item24_24_option" value="Beaumont">
        Beaumont
    </option>
        <option id="item24_25_option" value="Bellevue">
        Bellevue
    </option>
        <option id="item24_26_option" value="Berkeley">
        Berkeley
    </option>
        <option id="item24_27_option" value="Billings">
        Billings
    </option>
        <option id="item24_28_option" value="Birmingham">
        Birmingham
    </option>
        <option id="item24_29_option" value="Boise">
        Boise
    </option>
        <option id="item24_30_option" value="Boston">
        Boston
    </option>
        <option id="item24_31_option" value="Bridgeport">
        Bridgeport
    </option>
        <option id="item24_32_option" value="Bronx">
        Bronx
    </option>
        <option id="item24_33_option" value="Brooklyn">
        Brooklyn
    </option>
        <option id="item24_34_option" value="Brownsville">
        Brownsville
    </option>
        <option id="item24_35_option" value="Buffalo">
        Buffalo
    </option>
        <option id="item24_36_option" value="Burbank">
        Burbank
    </option>
        <option id="item24_37_option" value="California">
        California
    </option>
        <option id="item24_38_option" value="Cambridge">
        Cambridge
    </option>
        <option id="item24_39_option" value="Cape Coral">
        Cape Coral
    </option>
        <option id="item24_40_option" value="Carlsbad">
        Carlsbad
    </option>
        <option id="item24_41_option" value="Carrollton">
        Carrollton
    </option>
        <option id="item24_42_option" value="Cary">
        Cary
    </option>
        <option id="item24_43_option" value="Cedar Rapids">
        Cedar Rapids
    </option>
        <option id="item24_44_option" value="Centennial">
        Centennial
    </option>
        <option id="item24_45_option" value="Chandler">
        Chandler
    </option>
        <option id="item24_46_option" value="Charleston">
        Charleston
    </option>
        <option id="item24_47_option" value="Charlotte">
        Charlotte
    </option>
        <option id="item24_48_option" value="Chattanooga">
        Chattanooga
    </option>
        <option id="item24_49_option" value="Cheyenne">
        Cheyenne
    </option>
        <option id="item24_50_option" value="Chicago">
        Chicago
    </option>
        <option id="item24_51_option" value="Chula Vista">
        Chula Vista
    </option>
        <option id="item24_52_option" value="Cincinnati">
        Cincinnati
    </option>
        <option id="item24_53_option" value="Clarksville">
        Clarksville
    </option>
        <option id="item24_54_option" value="Clearwater">
        Clearwater
    </option>
        <option id="item24_55_option" value="Cleveland">
        Cleveland
    </option>
        <option id="item24_56_option" value="Colorado">
        Colorado
    </option>
        <option id="item24_57_option" value="Colorado Springs">
        Colorado Springs
    </option>
        <option id="item24_58_option" value="Columbia">
        Columbia
    </option>
        <option id="item24_59_option" value="Columbus">
        Columbus
    </option>
        <option id="item24_60_option" value="Concord">
        Concord
    </option>
        <option id="item24_61_option" value="Connecticut">
        Connecticut
    </option>
        <option id="item24_62_option" value="Coral Springs">
        Coral Springs
    </option>
        <option id="item24_63_option" value="Corona">
        Corona
    </option>
        <option id="item24_64_option" value="Corpus Christi">
        Corpus Christi
    </option>
        <option id="item24_65_option" value="Costa Mesa">
        Costa Mesa
    </option>
        <option id="item24_66_option" value="Dallas">
        Dallas
    </option>
        <option id="item24_67_option" value="Daly City">
        Daly City
    </option>
        <option id="item24_68_option" value="Dayton">
        Dayton
    </option>
        <option id="item24_69_option" selected value="Denver">
        Denver
    </option>
        <option id="item24_70_option" value="Des Moines">
        Des Moines
    </option>
        <option id="item24_71_option" value="Detroit">
        Detroit
    </option>
        <option id="item24_72_option" value="Dover">
        Dover
    </option>
        <option id="item24_73_option" value="Downey">
        Downey
    </option>
        <option id="item24_74_option" value="Durham">
        Durham
    </option>
        <option id="item24_75_option" value="El Monte">
        El Monte
    </option>
        <option id="item24_76_option" value="El Paso">
        El Paso
    </option>
        <option id="item24_77_option" value="Elgin">
        Elgin
    </option>
        <option id="item24_78_option" value="Elizabeth">
        Elizabeth
    </option>
        <option id="item24_79_option" value="Elk Grove">
        Elk Grove
    </option>
        <option id="item24_80_option" value="Erie">
        Erie
    </option>
        <option id="item24_81_option" value="Escondido">
        Escondido
    </option>
        <option id="item24_82_option" value="Eugene">
        Eugene
    </option>
        <option id="item24_83_option" value="Evansville">
        Evansville
    </option>
        <option id="item24_84_option" value="Everett">
        Everett
    </option>
        <option id="item24_85_option" value="Fairfield">
        Fairfield
    </option>
        <option id="item24_86_option" value="Fargo">
        Fargo
    </option>
        <option id="item24_87_option" value="Fayetteville">
        Fayetteville
    </option>
        <option id="item24_88_option" value="Flint">
        Flint
    </option>
        <option id="item24_89_option" value="Florida">
        Florida
    </option>
        <option id="item24_90_option" value="Fontana">
        Fontana
    </option>
        <option id="item24_91_option" value="Fort Collins">
        Fort Collins
    </option>
        <option id="item24_92_option" value="Fort Lauderdale">
        Fort Lauderdale
    </option>
        <option id="item24_93_option" value="Fort Wayne">
        Fort Wayne
    </option>
        <option id="item24_94_option" value="Fremont">
        Fremont
    </option>
        <option id="item24_95_option" value="Fresno">
        Fresno
    </option>
        <option id="item24_96_option" value="Frisco">
        Frisco
    </option>
        <option id="item24_97_option" value="Fullerton">
        Fullerton
    </option>
        <option id="item24_98_option" value="Gainsville">
        Gainsville
    </option>
        <option id="item24_99_option" value="Garden Grove">
        Garden Grove
    </option>
        <option id="item24_100_option" value="Garland">
        Garland
    </option>
        <option id="item24_101_option" value="Georgia">
        Georgia
    </option>
        <option id="item24_102_option" value="Gilbert">
        Gilbert
    </option>
        <option id="item24_103_option" value="Glendale">
        Glendale
    </option>
        <option id="item24_104_option" value="Grand Prairie">
        Grand Prairie
    </option>
        <option id="item24_105_option" value="Grand Rapids">
        Grand Rapids
    </option>
        <option id="item24_106_option" value="Green Bay">
        Green Bay
    </option>
        <option id="item24_107_option" value="Greensboro">
        Greensboro
    </option>
        <option id="item24_108_option" value="Gresham">
        Gresham
    </option>
        <option id="item24_109_option" value="Hampton">
        Hampton
    </option>
        <option id="item24_110_option" value="Hartford">
        Hartford
    </option>
        <option id="item24_111_option" value="Hawaii">
        Hawaii
    </option>
        <option id="item24_112_option" value="Hayward">
        Hayward
    </option>
        <option id="item24_113_option" value="Henderson">
        Henderson
    </option>
        <option id="item24_114_option" value="Hileah">
        Hileah
    </option>
        <option id="item24_115_option" value="High Point">
        High Point
    </option>
        <option id="item24_116_option" value="Hollywood">
        Hollywood
    </option>
        <option id="item24_117_option" value="Honolulu">
        Honolulu
    </option>
        <option id="item24_118_option" value="Houston">
        Houston
    </option>
        <option id="item24_119_option" value="Huntington">
        Huntington
    </option>
        <option id="item24_120_option" value="Huntington Beach">
        Huntington Beach
    </option>
        <option id="item24_121_option" value="Huntsville">
        Huntsville
    </option>
        <option id="item24_122_option" value="Idaho">
        Idaho
    </option>
        <option id="item24_123_option" value="Illinois">
        Illinois
    </option>
        <option id="item24_124_option" value="Independence">
        Independence
    </option>
        <option id="item24_125_option" value="Indiana">
        Indiana
    </option>
        <option id="item24_126_option" value="Indianapolis">
        Indianapolis
    </option>
        <option id="item24_127_option" value="Inglewood">
        Inglewood
    </option>
        <option id="item24_128_option" value="Iowa">
        Iowa
    </option>
        <option id="item24_129_option" value="Irvine">
        Irvine
    </option>
        <option id="item24_130_option" value="Irving">
        Irving
    </option>
        <option id="item24_131_option" value="Jackson">
        Jackson
    </option>
        <option id="item24_132_option" value="Jersey City">
        Jersey City
    </option>
        <option id="item24_133_option" value="Joilet">
        Joilet
    </option>
        <option id="item24_134_option" value="Kansas">
        Kansas
    </option>
        <option id="item24_135_option" value="Kansas City">
        Kansas City
    </option>
        <option id="item24_136_option" value="Kansas City MO">
        Kansas City MO
    </option>
        <option id="item24_137_option" value="Kentucky">
        Kentucky
    </option>
        <option id="item24_138_option" value="Kileen">
        Kileen
    </option>
        <option id="item24_139_option" value="Knoxville">
        Knoxville
    </option>
        <option id="item24_140_option" value="Lafayette">
        Lafayette
    </option>
        <option id="item24_141_option" value="Lakewood">
        Lakewood
    </option>
        <option id="item24_142_option" value="Lancaster">
        Lancaster
    </option>
        <option id="item24_143_option" value="Lansing">
        Lansing
    </option>
        <option id="item24_144_option" value="Laredo">
        Laredo
    </option>
        <option id="item24_145_option" value="Las Vegas">
        Las Vegas
    </option>
        <option id="item24_146_option" value="Lexington">
        Lexington
    </option>
        <option id="item24_147_option" value="Little Rock">
        Little Rock
    </option>
        <option id="item24_148_option" value="Long Beach">
        Long Beach
    </option>
        <option id="item24_149_option" value="Los Angeles">
        Los Angeles
    </option>
        <option id="item24_150_option" value="Louisiana">
        Louisiana
    </option>
        <option id="item24_151_option" value="Louisville">
        Louisville
    </option>
        <option id="item24_152_option" value="Lowell">
        Lowell
    </option>
        <option id="item24_153_option" value="Lubbock">
        Lubbock
    </option>
        <option id="item24_154_option" value="Madison">
        Madison
    </option>
        <option id="item24_155_option" value="Maine">
        Maine
    </option>
        <option id="item24_156_option" value="Manchester">
        Manchester
    </option>
        <option id="item24_157_option" value="Manhattan">
        Manhattan
    </option>
        <option id="item24_158_option" value="Maryland">
        Maryland
    </option>
        <option id="item24_159_option" value="Massachusetts">
        Massachusetts
    </option>
        <option id="item24_160_option" value="McAllen">
        McAllen
    </option>
        <option id="item24_161_option" value="McKinney">
        McKinney
    </option>
        <option id="item24_162_option" value="Mesa">
        Mesa
    </option>
        <option id="item24_163_option" value="Mesquite">
        Mesquite
    </option>
        <option id="item24_164_option" value="Miami">
        Miami
    </option>
        <option id="item24_165_option" value="Miami Gardens">
        Miami Gardens
    </option>
        <option id="item24_166_option" value="Michigan">
        Michigan
    </option>
        <option id="item24_167_option" value="Midland">
        Midland
    </option>
        <option id="item24_168_option" value="Milwaukee">
        Milwaukee
    </option>
        <option id="item24_169_option" value="Minneapolis">
        Minneapolis
    </option>
        <option id="item24_170_option" value="Minnesota">
        Minnesota
    </option>
        <option id="item24_171_option" value="Miramar">
        Miramar
    </option>
        <option id="item24_172_option" value="Mississippi">
        Mississippi
    </option>
        <option id="item24_173_option" value="Missouri">
        Missouri
    </option>
        <option id="item24_174_option" value="Mobile">
        Mobile
    </option>
        <option id="item24_175_option" value="Modesto">
        Modesto
    </option>
        <option id="item24_176_option" value="Montana">
        Montana
    </option>
        <option id="item24_177_option" value="Montgomery">
        Montgomery
    </option>
        <option id="item24_178_option" value="Moreno Valley">
        Moreno Valley
    </option>
        <option id="item24_179_option" value="Murfreesboro">
        Murfreesboro
    </option>
        <option id="item24_180_option" value="Murrieta">
        Murrieta
    </option>
        <option id="item24_181_option" value="Naperville">
        Naperville
    </option>
        <option id="item24_182_option" value="Nashville">
        Nashville
    </option>
        <option id="item24_183_option" value="Nebraska">
        Nebraska
    </option>
        <option id="item24_184_option" value="Nevada">
        Nevada
    </option>
        <option id="item24_185_option" value="New Hampshire">
        New Hampshire
    </option>
        <option id="item24_186_option" value="New Haven">
        New Haven
    </option>
        <option id="item24_187_option" value="New Jersey">
        New Jersey
    </option>
        <option id="item24_188_option" value="New Mexico">
        New Mexico
    </option>
        <option id="item24_189_option" value="New Orleans">
        New Orleans
    </option>
        <option id="item24_190_option" value="New York">
        New York
    </option>
        <option id="item24_191_option" value="New York City">
        New York City
    </option>
        <option id="item24_192_option" value="Newark">
        Newark
    </option>
        <option id="item24_193_option" value="Newport News">
        Newport News
    </option>
        <option id="item24_194_option" value="Norfolk">
        Norfolk
    </option>
        <option id="item24_195_option" value="Norman">
        Norman
    </option>
        <option id="item24_196_option" value="North Carolina">
        North Carolina
    </option>
        <option id="item24_197_option" value="North Dakota">
        North Dakota
    </option>
        <option id="item24_198_option" value="Norwalk">
        Norwalk
    </option>
        <option id="item24_199_option" value="Oakland">
        Oakland
    </option>
        <option id="item24_200_option" value="Oceanside">
        Oceanside
    </option>
        <option id="item24_201_option" value="Ohio">
        Ohio
    </option>
        <option id="item24_202_option" value="Oklahoma">
        Oklahoma
    </option>
        <option id="item24_203_option" value="Oklahoma City">
        Oklahoma City
    </option>
        <option id="item24_204_option" value="Olathe">
        Olathe
    </option>
        <option id="item24_205_option" value="Omaha">
        Omaha
    </option>
        <option id="item24_206_option" value="Ontario">
        Ontario
    </option>
        <option id="item24_207_option" value="Orange">
        Orange
    </option>
        <option id="item24_208_option" value="Oregon">
        Oregon
    </option>
        <option id="item24_209_option" value="Orlando">
        Orlando
    </option>
        <option id="item24_210_option" value="Overland Park">
        Overland Park
    </option>
        <option id="item24_211_option" value="Oxnard">
        Oxnard
    </option>
        <option id="item24_212_option" value="Palm Bay">
        Palm Bay
    </option>
        <option id="item24_213_option" value="Palmdale">
        Palmdale
    </option>
        <option id="item24_214_option" value="Pasadena">
        Pasadena
    </option>
        <option id="item24_215_option" value="Paterson">
        Paterson
    </option>
        <option id="item24_216_option" value="Pembroke Pines">
        Pembroke Pines
    </option>
        <option id="item24_217_option" value="Pennsylvania">
        Pennsylvania
    </option>
        <option id="item24_218_option" value="Peoria">
        Peoria
    </option>
        <option id="item24_219_option" value="Philadelphia">
        Philadelphia
    </option>
        <option id="item24_220_option" value="Phoenix">
        Phoenix
    </option>
        <option id="item24_221_option" value="Pittsburgh">
        Pittsburgh
    </option>
        <option id="item24_222_option" value="Plano">
        Plano
    </option>
        <option id="item24_223_option" value="Pomona">
        Pomona
    </option>
        <option id="item24_224_option" value="Port Saint Lucie">
        Port Saint Lucie
    </option>
        <option id="item24_225_option" value="Portland">
        Portland
    </option>
        <option id="item24_226_option" value="Providence">
        Providence
    </option>
        <option id="item24_227_option" value="Provo">
        Provo
    </option>
        <option id="item24_228_option" value="Pueblo">
        Pueblo
    </option>
        <option id="item24_229_option" value="Raleigh">
        Raleigh
    </option>
        <option id="item24_230_option" value="Rancho Cucamonga">
        Rancho Cucamonga
    </option>
        <option id="item24_231_option" value="Rhode Island">
        Rhode Island
    </option>
        <option id="item24_232_option" value="Richmond">
        Richmond
    </option>
        <option id="item24_233_option" value="Riverside">
        Riverside
    </option>
        <option id="item24_234_option" value="Rochester">
        Rochester
    </option>
        <option id="item24_235_option" value="Rockford">
        Rockford
    </option>
        <option id="item24_236_option" value="Roseville">
        Roseville
    </option>
        <option id="item24_237_option" value="Sacramento">
        Sacramento
    </option>
        <option id="item24_238_option" value="Saint Paul">
        Saint Paul
    </option>
        <option id="item24_239_option" value="Salem">
        Salem
    </option>
        <option id="item24_240_option" value="Salinas">
        Salinas
    </option>
        <option id="item24_241_option" value="Salt Lake City">
        Salt Lake City
    </option>
        <option id="item24_242_option" value="San Antonio">
        San Antonio
    </option>
        <option id="item24_243_option" value="San Benardino">
        San Benardino
    </option>
        <option id="item24_244_option" value="San Buenaventura">
        San Buenaventura
    </option>
        <option id="item24_245_option" value="San Diego">
        San Diego
    </option>
        <option id="item24_246_option" value="San Francisco">
        San Francisco
    </option>
        <option id="item24_247_option" value="San Jose">
        San Jose
    </option>
        <option id="item24_248_option" value="Santa Ana">
        Santa Ana
    </option>
        <option id="item24_249_option" value="Santa Clara">
        Santa Clara
    </option>
        <option id="item24_250_option" value="Santa Clarita">
        Santa Clarita
    </option>
        <option id="item24_251_option" value="Santa Rosa">
        Santa Rosa
    </option>
        <option id="item24_252_option" value="Savannah">
        Savannah
    </option>
        <option id="item24_253_option" value="Scottsdale">
        Scottsdale
    </option>
        <option id="item24_254_option" value="Seaside Heights">
        Seaside Heights
    </option>
        <option id="item24_255_option" value="Seattle">
        Seattle
    </option>
        <option id="item24_256_option" value="Shreveport">
        Shreveport
    </option>
        <option id="item24_257_option" value="Simi Valley">
        Simi Valley
    </option>
        <option id="item24_258_option" value="South Bend">
        South Bend
    </option>
        <option id="item24_259_option" value="South Carolina">
        South Carolina
    </option>
        <option id="item24_260_option" value="South Dakota">
        South Dakota
    </option>
        <option id="item24_261_option" value="Spokane">
        Spokane
    </option>
        <option id="item24_262_option" value="Springfiled">
        Springfiled
    </option>
        <option id="item24_263_option" value="St Louis">
        St Louis
    </option>
        <option id="item24_264_option" value="St Petersburg">
        St Petersburg
    </option>
        <option id="item24_265_option" value="Stamford">
        Stamford
    </option>
        <option id="item24_266_option" value="Staten Island">
        Staten Island
    </option>
        <option id="item24_267_option" value="Sterling Heights">
        Sterling Heights
    </option>
        <option id="item24_268_option" value="Stockton">
        Stockton
    </option>
        <option id="item24_269_option" value="Sunnyvale">
        Sunnyvale
    </option>
        <option id="item24_270_option" value="Surprise">
        Surprise
    </option>
        <option id="item24_271_option" value="Syracuse">
        Syracuse
    </option>
        <option id="item24_272_option" value="Tacoma">
        Tacoma
    </option>
        <option id="item24_273_option" value="Tallahassee">
        Tallahassee
    </option>
        <option id="item24_274_option" value="Tampa">
        Tampa
    </option>
        <option id="item24_275_option" value="Temecula">
        Temecula
    </option>
        <option id="item24_276_option" value="Tempe">
        Tempe
    </option>
        <option id="item24_277_option" value="Tennessee">
        Tennessee
    </option>
        <option id="item24_278_option" value="Texas">
        Texas
    </option>
        <option id="item24_279_option" value="Thorton">
        Thorton
    </option>
        <option id="item24_280_option" value="Thousand Oaks">
        Thousand Oaks
    </option>
        <option id="item24_281_option" value="Toledo">
        Toledo
    </option>
        <option id="item24_282_option" value="Topeka">
        Topeka
    </option>
        <option id="item24_283_option" value="Torrance">
        Torrance
    </option>
        <option id="item24_284_option" value="Tucson">
        Tucson
    </option>
        <option id="item24_285_option" value="Tulsa">
        Tulsa
    </option>
        <option id="item24_286_option" value="Utah">
        Utah
    </option>
        <option id="item24_287_option" value="Vallejo">
        Vallejo
    </option>
        <option id="item24_288_option" value="Vancover">
        Vancover
    </option>
        <option id="item24_289_option" value="Vermont">
        Vermont
    </option>
        <option id="item24_290_option" value="Victorville">
        Victorville
    </option>
        <option id="item24_291_option" value="Virginia">
        Virginia
    </option>
        <option id="item24_292_option" value="Virginia Beach">
        Virginia Beach
    </option>
        <option id="item24_293_option" value="Visalia">
        Visalia
    </option>
        <option id="item24_294_option" value="Waco">
        Waco
    </option>
        <option id="item24_295_option" value="Warren">
        Warren
    </option>
        <option id="item24_296_option" value="Washington">
        Washington
    </option>
        <option id="item24_297_option" value="Washington DC">
        Washington DC
    </option>
        <option id="item24_298_option" value="Waterbury">
        Waterbury
    </option>
        <option id="item24_299_option" value="West Covina">
        West Covina
    </option>
        <option id="item24_300_option" value="West Jordan">
        West Jordan
    </option>
        <option id="item24_301_option" value="West Valley City">
        West Valley City
    </option>
        <option id="item24_302_option" value="West Virginia">
        West Virginia
    </option>
        <option id="item24_303_option" value="Westminster">
        Westminster
    </option>
        <option id="item24_304_option" value="Wichita">
        Wichita
    </option>
        <option id="item24_305_option" value="Wichita Falls">
        Wichita Falls
    </option>
        <option id="item24_306_option" value="Williston">
        Williston
    </option>
        <option id="item24_307_option" value="Wilmington">
        Wilmington
    </option>
        <option id="item24_308_option" value="Winston Salem">
        Winston Salem
    </option>
        <option id="item24_309_option" value="Wisconsin">
        Wisconsin
    </option>
        <option id="item24_310_option" value="Worcester">
        Worcester
    </option>
        <option id="item24_311_option" value="Wyoming">
        Wyoming
    </option>
        <option id="item24_312_option" value="Yonkers">
        Yonkers
    </option>
      </datalist>
      <small id="nearHelp" class="form-text text-muted">If you would like a verified, screened, background checked, mvr checked driver. Sign Up for a verified account & submit to the same otherwise buyer beware. You have the make, model, color, license plate of vehicle what does the driver know about you besides username? Use common sense, show respect to others property get a ride from A to B. A HUMAN is stopping everything, risking their life, to come pick YOU up, you can always walk, jog, scooter, bike, skateboard, contact a friend, family member, co worker & offer them $10+ & Disrupt...Please note neither uber or lyft... verify millions of documents and are negligent in allowing millions of unsafe / uninsured vehicles on the street, they only verify background checks and motor vehicle records, not only is this a public safety issue but evidence of organized crime. Uber Lyft... owe a minimum $4 to every driver for every ride given on their platforms for wage theft. Uber Lyft take 50-90% of fares and pay illegal predatory wages from the 1960s that labor can't "choose" or "agree" to work for, a finders or connection fee is typically 10% this needs to be regulated and capped.</small>
    </div>
    <div class="form-group">
      <label><b>Pick Me or My Package Up Here |</b> <span onclick="getLocation()"><a href="#">Top Of Page</a></span></label>
      <input type="text" name="pickupLocation" id="pickupLocation" class="form-control">
      <input type="hidden" name="pickupLocationLink" id="pickupLocationLink">
      <small class="form-text text-muted">Enter Pick Up Address Above / Use Current Location</small>
    </div>

    <div class="form-group">
        <!--<label for="homeBaseZipCode"> Home Base Zip Code </label>-->
        <input type="hidden" value="80112" name="homeBaseZipCode" class="form-control">
    </div>

    <div class="form-group">
      <label><b>Drop Me or My Package Off Here</b></label>
      <input type="text" name="dropLocation" id="dropLocation" class="form-control">
      <input type="hidden" name="dropLocationLink" id="dropLocationLink">
      <small class="form-text text-muted">Enter Drop Off Address Above</small>
    </div>

    <div class="form-group">
        <!--<label for="desiredDestinationZipCode"> Desired Destination Zip Code </label>-->
        <input type="hidden" value="80112" name="desiredDestinationZipCode" class="form-control">
    </div>

    <div class="form-group vehicleType">
      <label><b>Vehicle Type (Driver Gets 90+%) Transparent Receipts</b></label>
      <select name="vehicleType" class="form-control vehicleType" id="vehicleType">
                                        <option class="option1" id="item42_0_option" selected value="a">
                                            a Ride (1-4 people) $2 per mile .30 per minute $10 MINIMUM
                                        </option>
                                        <option id="item42_1_option" value="XL">
                                            XL Ride (5-7 people More Room) $2.50 per mile .30 per minute $12 MINIMUM
                                        </option>
                                        <option id="item42_2_option" value="new">
                                            new Ride (1-4 people 3 Years or Newer) $3 per mile .30 per minute $15
                                            MINIMUM
                                        </option>
                                        <option id="item42_3_option" value="new XL">
                                            new XL Ride (5-7 More Room 3 Years or Newer Model $3.50 per mile .30
                                            per minute $20 MINIMUM
                                        </option>
                                        <option id="item42_4_option" value="Limo">
                                            Limo Party Shuttle Bus 1-10+ $5 per mile .50 per minute $100 MINIMUM
                                        </option>
                                        <option id="item42_5_option" value="Tow Truck">
                                            Tow Truck $5 per mile .50 per minute $50 minimum includes Jump Start /
                                            Gas / Lock Out Service
                                        </option>
                                    </select>
    </div>

    <div class="form-group">
      <label>Airline if applicable</label>
      <select name="airLine" id="airLine" class="form-control">
                                        <option id="item27_0_option" selected value="No airport Drop Off">
                                            No Airport Drop Off
                                        </option>
                                        <option id="item27_1_option" value="Alaska">
                                            Alaska
                                        </option>
                                        <option id="item27_2_option" value="Allegiant">
                                            Allegiant
                                        </option>
                                        <option value="American Airlines">
                                            American Airlines
                                        </option>
                                        <option value="Cayman Airways">
                                            Cayman Airways
                                        </option>
                                        <option id="item27_3_option" value="Delta">
                                            Delta
                                        </option>

                                        <option value="Denver Jet Center">

                                            Denver Jet Center

                                        </option>
                                        <option id="item27_4_option" value="Frontier">
                                            Frontier
                                        </option>
                                        <option id="item27_5_option" value="Hawaiian">
                                            Hawaiian
                                        </option>
                                        <option id="item27_6_option" value="Jet Blue">
                                            Jet Blue
                                        </option>

                                        <option value="Signature Flight Support">

                                            Signature Flight Support

                                        </option>
                                        <option id="item27_7_option" value="Southwest">
                                            Southwest
                                        </option>
                                        <option id="item27_8_option" value="Spirit">
                                            Spirit
                                        </option>
                                        <option id="item27_9_option" value="Sun Country">
                                            Sun Country
                                        </option>

                                        <option value="Tac Air">

                                            Tac Air

                                        </option>
                                        <option id="item27_10_option" value="United">
                                            United
                                        </option>
                                        <option id="item27_11_option" value="Other Airport Location">
                                            Other Airport Location
                                        </option>
                                    </select>
    </div>

    <div class="form-group">
      <label>Helpful Info for Driver</label>
      <select name="bags" id="bags" class="form-control">
                                        <option id="item41_0_option" selected value="No Checked Bags">
                                            No Checked Bags
                                        </option>
                                        <option id="item41_1_option" value="Checked Bags">
                                            Checked Bags
                                        </option>
                                        <option id="item41_2_option" value="Inside Check In">
                                            Inside Check In
                                        </option>
                                        <option id="item41_3_option" value="Outside Check In If Available">
                                            Outside Check In If Available
                                        </option>
                                    </select>
    </div>

    <div class="form-group">
      <label>Special Instructions</label>
      <textarea name="instructions" id="instructions" class="form-control"></textarea>
      <small class="form-text text-muted">Name Of Business, Hotel, Restauraunt, Store, Airline, Helpful Hints, Gate
      Codes, Description, Skis, Golf Bags....</small>
    </div>
    <div class="form-group">
      <label># of Riders</label>
      <select name="riders" class="form-control" id="riders">
                                        <option id="item43_0_option" selected value="1">
                                            1
                                        </option>
                                        <option id="item43_1_option" value="2">
                                            2
                                        </option>
                                        <option id="item43_2_option" value="3">
                                            3
                                        </option>
                                        <option id="item43_3_option" value="4">
                                            4 XL
                                        </option>
                                        <option id="item43_4_option" value="5 XL">
                                            5 XL
                                        </option>
                                        <option id="item43_5_option" value="6 XL">
                                            6 XL
                                        </option>
                                        <option id="item43_6_option" value="7 XL">
                                            7 XL
                                        </option>
                                        <option id="item43_7_option" value="8 Party Bus / Limo">
                                            8 Party Bus / Limo 
                                        </option>
                                        <option id="item43_8_option" value="9 Party Bus Limo">
                                            9 Party Bus Limo
                                        </option>
                                        <option id="item43_9_option" value="10 Party Bus Limo">
                                            10 Party Bus Limo
                                        </option>
                                        <option id="item43_10_option" value="10+ Party Bus Limo">
                                            10+ Party Bus Limo
                                        </option>
                                    </select>
    </div>

    <div class="form-group">
      <label>Service Animal(s) / Pets</label>
      <select name="pets" class="form-control" id="pets">
                                        <option id="item44_0_option" selected value="0 No Service Animals or Pets">
                                            0 No Service Animals or Pets
                                        </option>
                                        <option id="item44_1_option" value="1 Service Animal">
                                            1 Service Animal
                                        </option>
                                        <option id="item44_2_option" value="2 Service Animals">
                                            2 Service Animals
                                        </option>
                                        <option id="item44_3_option" value="3+ Service Animals">
                                            3+ Service Animals
                                        </option>
                                        <option id="item44_4_option" value="1 Pet">
                                            1 Pet
                                        </option>
                                        <option id="item44_5_option" value="2 Pets">
                                            2 Pets
                                        </option>
                                        <option id="item44_6_option" value="3+ Pets">
                                            3+ Pets
                                        </option>
                                    </select>
      <small class="form-text text-muted">Please don't lie about your pet being a service animal.</small>
    </div>

    <div class="form-group child_group">
      <div class="row">
        <div class="col-md-4">
          <label>Child Safety Seats | Wheelchair Accessible Vehicles or Special Needs</label>
          <select name="child" id="child" class="form-control">
                                        <option id="item45_0_option" selected value="No Child Safety Seat needed.">
                                            No Child Safety Seat needed.
                                        </option>
                                        <option id="item45_1_option" value="1 Safety Child Seat">
                                            1 Safety Child Seat
                                        </option>
                                        <option id="item45_2_option" value="2 Safety Child Seats">
                                            2 Safety Child Seats
                                        </option>
                                        <option id="item45_3_option" value="Wheelchair Accessible Vehicle | Special Needs">
                                            Wheelchair Accessible Vehicle | Special Needs
                                        </option>
                                    </select>
          <small class="form-text text-muted">Drivers do not provide child safety seats. Rider must install &amp; provide child safety seats for each child as required by law. Please include any special instructions if you need a vehicle adapted for a disability.</small>
        </div>
        <div class="col-md-8">
          <div class="form-group">
            <label>Tips NOT required always appreciated. DRIVER Receives 100% | Tolls, Bad Weather, Event Just Ended, Traffic, Late PM Early AM, Dangerous Zip Code, Multiple Stops...?</label>
            <select name="tip" id="tip" class="form-control">
                                        <option id="item22_0_option" selected value="5">
                                            5% Drivers do EARN a legal wage.
                                        </option>
                                        <option id="item22_1_option" value="10">
                                            10%
                                        </option>
                                        <option id="item22_2_option" value="15">
                                            15%
                                        </option>
                                        <option id="item22_3_option" value="20">
                                            20%
                                        </option>
                                        <option id="item22_4_option" value="25">
                                            25%
                                        </option>
                                        <option id="item22_5_option" value="30">
                                            30%
                                        </option>
                                        <option id="item22_6_option" value="35">
                                            35%
                                        </option>
                                        <option id="item22_7_option" value="40">
                                            40%
                                        </option>
                                        <option id="item22_8_option" value="45">
                                            45%
                                        </option>
                                        <option id="item22_9_option" value="50">
                                            50%
                                        </option>
                                        <option id="item22_10_option" value="55">
                                            55%
                                        </option>
                                        <option id="item22_11_option" value="60">
                                            60%
                                        </option>
                                        <option id="item22_12_option" value="65">
                                            65%
                                        </option>
                                        <option id="item22_13_option" value="70">
                                            70%
                                        </option>
                                        <option id="item22_14_option" value="75">
                                            75%
                                        </option>
                                        <option id="item22_15_option" value="80">
                                            80%
                                        </option>
                                        <option id="item22_16_option" value="85">
                                            85%
                                        </option>
                                        <option id="item22_17_option" value="90">
                                            90%
                                        </option>
                                        <option id="item22_18_option" value="95">
                                            95%
                                        </option>
                                        <option id="item22_19_option" value="100">
                                            100%
                                        </option>
                                        <option id="item22_20_option" value="150">
                                            150%
                                        </option>
                                        <option id="item22_21_option" value="200">
                                            200%
                                        </option>
                                    </select>
            <small class="form-text text-muted">Try Increasing This Amount To Increase Your Drivers Motivation during such circumstances....</small>
          </div>
        </div>
      </div>
    </div>
    </div>
    </div>
    <!-- ride container end here -->

    <!-- Start delivery container -->
    <div class="row">
      <div class="container">
        <div class="delivery-fields col-md-12 col-lg-12 col-sm-12">
          <br>
          <div class="delivery-group">
            <div class="form-group">
              <label>Delivery $10-$50+ Over Receipts Enter, Choose, or Search for Place Below (Add your location $21)</label>
              <input name="select30" list="select30List" id="item30_select_1" class="form-control" value="">
              <datalist id="select30List">
                <?php   $deliveryOptions = [' ','Walmart', 'Albertsons', 'Aldi', 'Best Buy', 'Costco', 'CVS', 'Dollar General', 'Food Lion', 'Home Depot', 'Kroger', 'Lowes', 'McDonalds', 'Publix', 'Sams Club', 'Target', 'Walgreens', '1UP','7-Eleven','99 Cents Only Stores','99 Ranch Market','A & W Restaurants','A Peace of Soul Vegan Kitchen Columbia South Carolina','AJ\'s Fine Foods','ALLeon’s Thriftway','Ace Beauty Supply','Ace Hardware Store','Acme Fresh Market','Acme Markets','Advanced Auto Parts','Albertsons','Aldis','Aloha','Amigos Mexican Food Centennial CO','Amy Ruth’s New York City New York Soul Food','Angie’s Soul Cafe Cleveland Soul Food','Annie’s Beauty Supply','Aplus','Apple Store','Apples And Oranges Fresh Market','Arby\'s','Arctic Circle Restaurants','Arnold’s Country Kitchen Nashville Soul Food','Arthur Treacher\'s','Atlanta Beauty Depot','Atlanta Bread Company','Au Bon Pain','Auntie Anne\'s','Auto Zone Auto Parts','BI-LO','Bahama Breeze','Baja Fresh','Bar Louie','Bashas','Baskin-Robbins','Beans & Cornbread Southfield Michigan Soul Food','Beaute’ Mark Beauty Supply',
                                                                'Beauty Fab Lab Beauty Supply','Beauty For U Beauty Supply','Benihana','Benne on Eagle Asheville North Carolina Soul Food','Benton’s Beauty Supply','Bertucci\'sS','Best Buy','Big Apple Beauty Supply','Big Lots','Big Mike’s Soul Food Myrtle Beach South Carolina','Big Y','Bill Miller Bar-B-Q1','BJ\'s','Black Angus Steakhouse','Black Girls Divine Beauty Supply And Salon','Black Star Beauty Supply','Blaze Pizza','Blimpie','Blueprints','Bojangles\' Famous Chicken \'n Biscuits','Bolton’s Spicy Chicken & Fish Nashville Soul Food','Bonanza Steakhouses','Bonefish Grill','Boston Market','Braum\'s','Brio Tuscan Grill','Brixton Beauty Supply','Brooklyn Soul Food Cleveland','Brookshire Brothers','Bubba Gump Shrimp Company','Buehler\'s','Buffalo Wild Wings','Bully’s Restaurant Jackson Mississippi Soul Food','Burger Chef','Burger King','Burger Street','Burgerville','Busy Bee Cafe Atlanta Georgia Soul Food','CMB Soul Food Cleveland','CVS','California Pizza Kitchen','Call Me Cake Las Vegas Soul Food','Capital Grille','Captain D\'s Seafood Kitchen','Car Quest Auto Parts','Caribou Coffee','Carino\'s Italian Grill','Carl\'s Jr.','Carrabba\'s Italian Grill','Casey’s Buffet Wilmington North Carolina Soul Food','Casey’s General Stores','Cash & Carry','Charles’ Country Pan Fried Chicken New York Soul Food','Charleys Philly Steaks','Checkers  Rallys','Cheeburger Cheeburger','Chevys Fresh Mex','Chick-fil-A','Chicken Express','Chili\'s','Chipotle Mexican Grill','Chuck E. Cheese Pizza','Chuck-A-Rama','Church\'s Texas Chicken','Cinnabon','Circle',
                                                                'Circle Foods','City Market','Claim Jumper','Coco\'s Bakery','Coffee Bean & Tea Leaf','Cold Stone Creamery','Cook Out','Copeland\'s','County Market','Cracker Barrel','Croaker’s Spot Richmond Virginia Soul Food','Crown of Glory Beauty Supply','Cub Foods','Culver\'s','Cumberland Farms','Curl Kitchen Beauty Supply','D&W Fresh Market','D\'Agostino Supermarkets','Dairy Queen','Dave & Busters','Dave’s Dinners Express Akron OH','Del Frisco\'s','Del Taco','Delta’s New Brunswick New Jersey Soul Food','Denny\'s','Deon’s Restaurant Cleveland Soul Food','Desired Beauty Supplies','DiBella\'s','Dickey\'s Barbecue Pit','Dierbergs Markets','Discount Queen Beauty Supply','Dixie Chili and Deli','Dixon’s Soul Food Nashville Soul Food','Doc and Lennies Cleveland Soul Food','Dollar General','Dollar Tree',
                                                                'Dominos Pizza','Dooky Chase’s New Orleans Louisiana Soul Food','Double Eagle Steakhouse','Dreamz Cafe Cleveland Soul Food','Druther\'s','Duchess','Duck Donuts','Dunkin\' Donuts','Econofoods','Eddie V\'s Prime Seafood','Eegee\'s','El Chico','El Pollo Loco','El Taco Tote','Elevation Burger','Elise Beauty Supply','Ella Em’s Soul Food Las Vegas','Ella Mae’s Soulfood Cleveland Soul Food','EllaEm\'s Soul Food Las Vegas Soul Food','Empress Beauty Supply','Epic Curls Beauty Supply','Essence of it Beauty Supply','Esther’s Cajun Cafe and Soul Food Houston','Family Dollar','Famous Dave\'sH','Farmer Boys','Fatburger','Festival Foods','Firehouse Subs','Five Guys Burgers and Fries','Five Sisters Blues Café Pensacola Florida Soul Food','Fleming\'s Prime Steakhouse & Wine','Florida Avenue Grill Washington DC Soul Food','Flowers','Flying J','Fogo de Chão','Food 4 Less','Food 4 Less','Food Lion','Food Town','Foodland','Foot Locker','Fosters Freeze','Fred Meyer',
                                                                'Freddy\'s Frozen Custard & Steakburgers','Friday’s Beauty Supply','Frys Electronics','G\'s Jamaican Quisine','Garden of Natural Beauty','Get Sassy Beauty Supply','Giant Eagle','Giant Eagle','Giant Food Stores','Global Pet Foods','Go Natural 24/7 Beauty Supply','Gold Star Chili','Golden Chick','Good Times Burgers & Frozen Custard','Grandpa\'s Southern BBQ Idaho Falls Idaho','Grandy\'s','Green Burrito Red Burrito','Green Elephant Vegetarian Bistro','Gritz Cafe Las Vegas Soul Food','Grocery Outlet','Grocery Outlet Compton','H Mart','H&T’s Home Cooking Nashville Soul Food','H-E-B',
                                                                'Hair Couture Beauty Supply','Hair Zone Beauty Supply','Hairizon Beauty Beauty Supply','Happy Joe\'s','Harbor Freight','Hardee\'s','Harmons Grocery','Harps Food Stores','Harvard Soul Bistro Cleveland Soul Food','Harvey\'s','Head Games Beauty Supply',
                                                                'Healthy Hair Dimensions','Heights Soul Food & Grille Cleveland','Heinen\'s Fine Foods','Hollywood Feed','Home Depot','Homeland','Hooters','Hooters','Hoover’s Cooking Austin Texas Soul Food','Hot Mongolian Grill','Hot Sauce Williams Barbecue','Huddle House','Hugo\'s','Hurricane Grill & Wings','Husk Nashville Soul Food','IBI Beauty Supply Store','IHOP','IKEA','Illegal Petes','In-N-Out Burger','Ingles Markets','Jack in the Box','Jack\'s','Jamba Juice','Jason\'s Deli','Jerry\'s Foods','Jersey Mike\'s Subs','Jewel-Osco','Jewel’s Beauty Supply','Jim\'s Restaurants','Jimmy Buffett\'s Margaritaville','Jimmy John\'s','Joe\'s Crab Shack','Johnny Rockets','Jordan’s Beauty Supply','JuneBaby Seattle Washington Soul Food','KD Haircare Supply, LLC','KFC Kentucky Fried Chicken','Kewpee','Key Food','King Kullen','King Soopers','Kona Grill','Kountry Kitchen Soul Food Place Indianapolis Indiana','Krispy Kreme','Kroger','Kroger','Krystal','Kum & Go','Kuzzo’s Chicken & Waffles Detroit Michigan Soul Food','Kwik Shop','Kwik Star','Kwik Trip','L&L Hawaiian Barbecue','LA Soul Akron OH','Landry\'s Seafood','Lee Roy Selmon\'s','Lee\'s Famous Recipe Chicken','Leslie’s True Soul Food Cleveland','Lidz','Lin’s Beauty Supply','Lion\'s Choice','Little Caesars Pizza','Lo-Lo\'s Chicken and Waffles Soul Food','Logan\'s Roadhouse','Lola\'s A Louisana Kitchen Las Vegas Soul Food','Long John Silver\'s','LongHorn Steakhouse','Loveless Cafe Nashville Soul Food','Lowes','Lowes Foods','Lucille\'s Smokehouse Bar-B-Que','Lunds & Byerlys','Lynn’s Beauty Supply','M & M Soul Food Las Vegas Soul Food','MD Calhoun food grocery','MOD Pizza','Madison Grille Cleveland Soul Food','Maggiano\'s Little Italy','Maid-Rite','Majestic Beauty Supplies','Mane & Beat Beauty Boutique','Marc\'s','Marco\'s Pizza','Market Basket','Market Basket','Martha Lou’s Kitchen Charleston South Carolina Soul Food','Max & Erma\'s','McAlister\'s Deli','McCormick & Schmick\'s','McDonald\'s','Meijers','Mellow Mushroom','Melting Pot','Menards Hardware Store','Met Foodmarkets','Micro Center','Mikki’s Soul Food & Catering Houston Texas Soul Food','Miller\'s Ale House','Milo\'s Hamburgers','Mimi\'s Cafe','Mine, Naturally Beauty Supply','Moe\'s Southwest Grill','Monell’s Nashville Soul Food','Monique’s Natural Hair Boutique','Montana Mike\'s','Mooyah','Morton Williams','Morton\'s Steakhouse','Mr. MamasbLas Vegas Soul Food','Mrs. Fields','Mrs. White’s Golden Rule Cafe Phoenix Arizona Soul Food','Mrs. Wilkes Dining Room Savannah Georgia Soul Food','Mrs. Winner\'s Chicken & Biscuits','Murphy Express','My Beauty Beauty Supply','Napa Auto Parts','Natalya’s Beauty Supply','Naugles','New Seasons Market','Niecie’s Restaurant Kansas City Missouri Soul Food','Noodles and Company','Nugget Markets','O Reilly Auto Parts','Office Depot','Office Max','Old Chicago Pizza & TaproomB','Old Spaghetti Factory','Olive Garden','On the Border Mexican Grill & Cantina','Oohh’s & Aahh’s Washington DC Soul Food','Open Pitt Barbeque Carryout Cleveland Soul Food','Outback Steakhouse','P.F. Chang\'s China Bistro','PD & K Beauty Supply','PETCO','Panda Express','Panera Bread','Papa John\'s Pizza','Papa Murphy\'s Pizza','Pappadeaux Seafood Kitchen','Pappas Bar-B-Q','Pappasito\'s Cantina','Paris Hair & Beauty Supplies','Patel Brothers','Payne’s Beauty & Barber Supply','Pearl’s Place Chicago Soul Food','Penn Station East Coast Subs','Pep Boys Auto Parts','Perkins Restaurant and Bakery','Personal Treasures Salon & Spa','Pet People','Pet Supermarket','Pet Supplies Plus','Pet Valu','PetSmart','Peter Piper Pizza','Petland','Petsense','Piggly Wiggly','Pilot Travel Centers','Pita Pit','Pizza Hut','Pizza Inn','Pollo Tropical','Ponderosa','Popeyes Chicken & Biscuits','Port of Subs','Potbelly Sandwich Works','Powell’s Barber and Beauty','Price Chopper','Price Rite','PriceRite','Publix','QFC','Qdoba','Quik Stop','Quizno\'s Classic Subs','Raising Cane\'s Chicken Fingers','Raley\'s Supermarkets','Ralphs','Rax','Reasors','Red Hot & Blue','Red Lobster','Red Robin','Redner\'s Markets','Remke Markets','Ridley\'s Family Markets','Rite Aid','Robeks','Rollin\' Smoke Barbeque Las Vegas Soul Food','Romano\'s Macaroni Grill','Rosati\'s Authentic Chicago Pizza','Rosauers Supermarkets','Roscoe’s House of Chicken and Waffles California Soul Food','Round Table Pizza','Roundy\'s','Rouses','Roy Rogers Restaurants','Roy\'s','Ruler Foods','Ruth\'s Chris Steak House','Safeway','Saladworks','Sallys Beauty Supply','Sally’s Famous Kitchen Nashville Soul Food','Saltgrass Steak House','Sam\'s Clubs','Savannah Blue Detroit','Save Mart Supermarkets','Save-A-Lot','Sbarro Pizza','Schlotzsky\'s Deli','Scolari\'s Food and Drug','Seafood City','Seattle\'s Best Coffee','Sedanos','Seller\'s Brothers','Sendik\'s Food Market','Sephoras','Seven Mile Market','Shake Shack','Shakey\'s Pizza','Sharp Shopper','Shaw\'s','Shirley Mae\'s Cafe Louisville Kentucky Soul Food','Shoppers Food & Pharmacy','Sisters Beauty Supply','Sisters Too Beauty Supply','Sizzler','Skyline Chili','Smart & Final','Smashburger','Smith\'s Food and Drug','Smokey Bones','Sneaky Pete\'s','Sonic Drive-In','Sonny\'s BBQ','Soul Foo Young Las Vegas Soul Food','Soul Food Cafe Las Vegas','Souper Salads','Southeastern Grocers','Southern Cafe Cleveland Soul Food','Spangles','Speedway','Sprouts','Staples','Starbucks','Stater Brothers','Steak \'n Shake','Steak Escape','Stir Crazy','Stop & Shop','Stripes','Subway','Summertime Soul Food Las Vegas','Super One Foods','SuperFresh','Sweet Georgia Brown Dallas Texas Soul Food','Sweet Potatoes Winston-Salem North Carolina Soul Food','Sweet Soulfood New Orleans Louisiana','Sweetie Pie’s Upper Crust St. Louis Missouri Soul Food','Swensen\'s','Swensons','Sylvia’s New York Soul Food','Systahood Beauty Supplies','T Mobile Store','TC\'s Rib CribbLas Vegas Soul Food','Taco Bell','Taco Bueno','Taco Cabana','Taco John\'s','Taco Mayo','Taco Tico','Taco Time','Talessia’s Kitchen Cleveland Soul Food','Target','Tendrils and Curls Beauty Supply','Texas Roadhouse','The Habit Burger Grill','The Halal Guys','This Is It Houston Texas Soul Food','Tigermarket','Tim Hortons','Times Supermarkets','Tony Roma\'s','TopNotch.net','Tops','Torchys Tacos','Tractor Supply','Trader Joes','Trina’s Beauty Supply','Tru Mane Beauty Supply','True Value Hardware Store','Tudor\'s Biscuit World','Twin Peaks','Umami Burger','United Grocery Outlet','Uno Pizzeria & Grill','Valentino\'s','Verizon Store','Vivrant Beauty','Vons','V’s Gourmet Chicken Cleveland Soul Food','Waffle House','Wahlburgers','Walgreens','Walmart','Wawa','Wendy\'s','Western Beef','Western Sizzlin','Wetzel\'s Pretzels','Whataburger','White Castle','Whitts Barbeque','Whole Foods','Wienerschnitzel','Willie Mae’s Scotch House New Orleans Soul Food','Wilsons Hot Tamales Cleveland','Wimpy','WinCo Foods','WinCo Foods','WingHouse Bar & Grill','Wingstop','Winn-Dixie','Woodman\'s Food Market','Woof Gang Bakery',
                                                                'Yardbird Southern Table & Bar Las Vegas Soul Food','Yoke\'s Fresh Market','Zanzibar Soul Fusion Cleveland Soul Food','Zaxby\'s','Zippy','ZuZu Beauty Supply'
                                                ];
                                            foreach($deliveryOptions as $sOps){
                                                $selectOption = ' <option value="'.$sOps.'">'.$sOps.'</option>';
                                                echo $selectOption;
                                            }?>                                                                              
              </datalist>
            </div>
            <div class="form-group">
              <label>I&#39m willing to PAY...(More Items More Weight More Stops More Errands = More $)</label>
              <select name="select46" id="item46_select_1" class="form-control">
                                        <option selected value="No Delivery">
                                            No Delivery
                                        </option>
                                        <option id="item46_0_option" value="$20 over receipt">
                                            $20 over receipt
                                        </option>
                                        <option id="item46_1_option" value="$15 over Receipt">
                                            $15 over Receipt
                                        </option>
                                        <option id="item46_2_option" value="$10 over receipt">
                                            $10 over receipt
                                        </option>
                                        <option id="item46_3_option" value="$25 over receipt">
                                            $25 over receipt
                                        </option>
                                        <option id="item46_4_option" value="$30 over receipt">
                                            $30 over receipt
                                        </option>
                                        <option id="item46_5_option" value="$35 over receipt">
                                            $35 over receipt
                                        </option>
                                        <option id="item46_6_option" value="$40 over receipt">
                                            $40 over receipt
                                        </option>
                                        <option id="item46_7_option" value="$45 over receipt">
                                            $45 over receipt
                                        </option>
                                        <option id="item46_8_option" value="$50 over receipt">
                                            $50 over receipt
                                        </option>
                                        <option id="item46_9_option" value="$75 over receipt">
                                            $75 over receipt
                                        </option>
                                        <option id="item46_10_option" value="$100 over receipt">
                                            $100 over receipt
                                        </option>
                                        <option id="item46_11_option" value="$150 over receipt">
                                            $150 over receipt
                                        </option>
                                        <option id="item46_12_option" value="$300 over receipt">
                                            $300 over receipt
                                        </option>
                                    </select>
            </div>
            <div class="form-group">
              <label>Special Delivery Instructions Please Place Detailed Item List & Delivery Info Here</label>
              <textarea type="text" name="dInstructions" class="form-control" id="dInstructions"></textarea>
            </div>
            <br>
          </div>
        </div>
      </div>
    </div>
    <!-- delivery container end here -->
    <div class="container coming-soon-select form-group">
      <label>Coming Soon You're Hired. Independent Contractors (Add your local business below $21)</label>
      <!-- <input name="select47" id="item47_select_1" class="form-control"> -->
      <select name="select47" id="item47_select_1" class="form-control mw-inherit coming-soon-option">  
                                        <option class="big-option d-flex flex-wrap" selected value="Certified Local Teachers Home Schooling Class Rooms Mon-Fri 10 Students Max">
                                            Certified Local Teachers Home Schooling Class Rooms Mon-Fri 10 Students Max
                                        </option>
                                        <option id="item47_0_option" value="Grade Level 1 (05-06)">
                                            Pre-School (05-06)
                                        </option>
                                        <option id="item47_1_option" value="Grade Level 1 (07)">
                                            Grade Level 1 (07)
                                        </option>
                                        <option id="item47_2_option" value="Grade Level 2 (08)">
                                            Grade Level 2 (08)
                                        </option>
                                        <option id="item47_3_option" value="Grade Level 3 (09)">
                                            Grade Level 3 (09)
                                        </option>
                                        <option id="item47_4_option" value="Grade Level 4 (10)">
                                            Grade Level 4 (10)
                                        </option>
                                        <option id="item47_5_option" value="Grade Level 5 (11)">
                                            Grade Level 5 (11)
                                        </option>
                                        <option id="item47_6_option" value="Grade Level 6 (12)">
                                            Grade Level 6 (12)
                                        </option>
                                        <option id="item47_7_option" value="Grade Level 7 (13)">
                                            Grade Level 7 (13)
                                        </option>
                                        <option id="item47_8_option" value="Grade Level 8 (14)">
                                            Grade Level 8 (14)
                                        </option>
                                        <option id="item47_9_option" value="Grade Level 9 (15)">
                                            Grade Level 9 (15)
                                        </option>
                                        <option id="item47_10_option" value="Grade Level 10 (16)">
                                            Grade Level 10 (16)
                                        </option>
                                        <option id="item47_11_option" value="Grade Level 11 (17)">
                                            Grade Level 11 (17)
                                        </option>
                                        <option id="item47_12_option" value="Grade Level 12 (18)">
                                            Grade Level 12 (18)
                                        </option>
                                        <option id="item47_13_option" value="University">
                                            University
                                        </option>
                                        <option id="item47_14_option" value="Apprentice">
                                        Local Apprentice
                                        </option>
                                        <option id="item47_15_option" value="Mentor">
                                            Local Mentor
                                        </option>
                                        <option id="item47_16_option" value="Trade">
                                            Local Trade
                                        </option>
                                        <option id="item47_17_option" value="Local 4sale">
                                            Local 4sale
                                        </option>
                                        <option id="item47_18_option" value="Local Accounting">
                                            Local Accounting
                                        </option>
                                        <option id="item47_19_option" value="Local Advertising">
                                            Local Advertising
                                        </option>
                                        <option id="item47_20_option" value="Local Air Conditioning HVAC Repair">
                                        Local Air Conditioning HVAC Repair
                                        </option>                                    
                                        <option id="item47_21_option" value="Local Artist">
                                        Local Artist
                                        </option>
                                        <option id="item47_22_option" value="Local Auto Detailing">
                                        Local Auto Detailing
                                        </option>
                                        <option id="item47_23_option" value="Local Auto Repair">
                                            Local Auto Repair
                                        </option>
                                        <option id="item47_24_option" value="Local Autobody Paint">
                                            Local Autobody Paint
                                        </option>
                                        <option id="item47_25_option" value="Local Baker">
                                            Local Baker
                                        </option>
                                        <option id="item47_26_option" value="Local Band">
                                            Local Band
                                        </option>
                                        <option id="item47_27_option" value="Local Bar Tender Mixologist">
                                            Local Bar Tender Mixologist
                                        </option>
                                        <option id="item47_28_option" value="Local Barber">
                                            Local Barber
                                        </option>
                                        <option id="item47_29_option" value="Local Beauty Shop">
                                            Local Beauty Shop
                                        </option>
                                        <option id="item47_30_option" value="Local Bed & Breakfast BNB">
                                            Local BNB Bed & Breakfast
                                        </option>
                                        <option id="item47_31_option" value="Local Bodyguard">
                                            Local Bodyguard
                                        </option>
                                        <option id="item47_32_option" value="Local Bondsman">
                                            Local Bondsman
                                        </option>
                                        <option id="item47_33_option" value="Local Bricklayers">
                                            Local Bricklayers
                                        </option>
                                        <option id="item47_34_option" value="Local Butcher">
                                            Local Butcher
                                        </option>
                                        <option id="item47_35_option" value="Local Car Rental">
                                            Local Car Rental
                                        </option>
                                        <option id="item47_36_option" value="Local Carpenter">
                                            Local Carpenter
                                        </option>
                                        <option id="item47_37_option" value="Local Carpet">
                                            Local Carpet
                                        </option>
                                        <option id="item47_38_option" value="Local Catering">
                                            Local Catering
                                        </option>
                                        <option id="item47_39_option" value="Local Charity">
                                            Local Charity
                                        </option>
                                        <option id="item47_40_option" value="Local Child Care">
                                            Local Child Care
                                        </option>
                                        <option id="item47_41_option" value="Local Coder">
                                            Local Coder
                                        </option>
                                        <option id="item47_42_option" value="Local Computer Repair">
                                            Local Computer Repair
                                        </option>
                                        <option id="item47_43_option" value="Local Concrete Finisher">
                                            Local Concrete Finisher
                                        </option>
                                        <option id="item47_44_option" value="Local Cook Chef">
                                            Local Cook Chef
                                        </option>
                                        <option id="item47_45_option" value="Local Crane Operator">
                                            Local Crane Operator
                                        </option>
                                        <option id="item47_46_option" value="Local DJ">
                                            Local DJ
                                        </option>
                                        <option id="item47_47_option" value="Local Data Entry">
                                            Local Data Entry
                                        </option>
                                        <option id="item47_48_option" value="Local Data Recovery">
                                            Local Data Recovery
                                        </option>
                                        <option id="item47_49_option" value="Local Day Care">
                                            Local Day Care
                                        </option>
                                        <option id="item47_50_option" value="Local Dentist">
                                            Local Dentist
                                        </option>
                                        <option id="item47_51_option" value="Local Doctor">
                                            Local Doctor
                                        </option>
                                        <option id="item47_52_option" value="Local Dog Trainer">
                                            Local Dog Trainer
                                        </option>
                                        <option id="item47_53_option" value="Local Dog Walker">
                                            Local Dog Walker
                                        </option>
                                        <option id="item47_54_option" value="Local Driving Instructor">
                                            Local Driving Instructor
                                        </option>
                                        <option id="item47_55_option" value="Local Electrician">
                                            Local Electrician
                                        </option>
                                        <option id="item47_56_option" value="Local Fish Seafood">
                                            Local Fish Seafood
                                        </option>
                                        <option id="item47_57_option" value="Local Flight Simulator Training">
                                            Local Flight Simulator Training
                                        </option>
                                        <option id="item47_58_option" value="Local Flooring Installer">
                                            Local Flooring Installer
                                        </option>
                                        <option id="item47_59_option" value="Local Gardener">
                                            Local Gardener
                                        </option>
                                        <option id="item47_60_option" value="Local General Labor">
                                            Local General Labor
                                        </option>
                                        <option id="item47_61_option" value="Local Glass Work">
                                            Local Glass Work
                                        </option>
                                        <option id="item47_62_option" value="Local Glaziers">
                                            Local Glaziers
                                        </option>
                                        <option id="item47_63_option" value="Local HAM Radio">
                                            Local HAM Radio
                                        </option>
                                        <option id="item47_64_option" value="Local Hairbraider">
                                            Local Hairbraider
                                        </option>
                                        <option id="item47_65_option" value="Local Hairdresser">
                                            Local Hairdresser
                                        </option>
                                        <option id="item47_66_option" value="Local Heavy Machinery Operator">
                                            Local Heavy Machinery Operator
                                        </option>
                                        <option id="item47_67_option" value="Local Heavy Truck Driver">
                                            Local Heavy Truck Driver
                                        </option>
                                        <option id="item47_68_option" value="Local Home Theater Consultant">
                                            Local Home Theater Consultant
                                        </option>
                                        <option id="item47_69_option" value="Local Horse Riding">
                                            Local Horse Riding
                                        </option>
                                        <option id="item47_70_option" value="Local Hotel Room">
                                            Local Hotel Room
                                        </option>
                                        <option id="item47_71_option" value="Local Housekeeping">
                                            Local Housekeeping
                                        </option>
                                        <option id="item47_72_option" value="Local Insulation Workers">
                                            Local Insulation Workers
                                        </option>
                                        <option id="item47_73_option" value="Local Investment Advisor">
                                            Local Investment Advisor
                                        </option>
                                        <option id="item47_74_option" value="Local Jeweler">
                                            Local Jeweler
                                        </option>
                                        <option id="item47_75_option" value="Local Landscaping">
                                            Local Landscaping
                                        </option>
                                        <option id="item47_76_option" value="Local Lawyer">
                                            Local Lawyer
                                        </option>
                                        <option id="item47_77_option" value="Local Legislation Analyst">
                                            Local Legislation Analyst
                                        </option>
                                        <option id="item47_78_option" value="Local Locksmith">
                                            Local Locksmith
                                        </option>
                                        <option id="item47_79_option" value="Local Media">
                                            Local Media
                                        </option>
                                        <option id="item47_80_option" value="Local Money Lender">
                                            Local Money Lender
                                        </option>
                                        <option id="item47_81_option" value="Local Motorcycle Instructor">
                                            Local Motorcycle Instructor
                                        </option>
                                        <option id="item47_82_option" value="Local Music Instructor">
                                            Local Music Instructor
                                        </option>
                                        <option id="item47_83_option" value="Local Musician">
                                            Local Musician
                                        </option>
                                        <option id="item47_84_option" value="Local News">
                                            Local News
                                        </option>
                                        <option id="item47_85_option" value="Local Painter">
                                            Local Painter
                                        </option>
                                        <option id="item47_86_option" value="Local Producer">
                                            Local Producer
                                        </option>
                                        <option id="item47_87_option" value="Local Pawnbroker">
                                            Local Pawnbroker
                                        </option>
                                        <option id="item47_89_option" value="Local Pediatrician">
                                            Local Pediatrician
                                        </option>
                                        <option id="item47_90_option" value="Local Pen Testing">
                                            Local Pen Testing
                                        </option>
                                        <option id="item47_91_option" value="Local Personal Trainer">
                                            Local Personal Trainer
                                        </option>
                                        <option id="item47_92_option" value="Local Pest Removal">
                                            Local Pest Removal
                                        </option>
                                        <option id="item47_93_option" value="Local Pet Care">
                                            Local Pet Care
                                        </option>
                                        <option id="item47_94_option" value="Local Pet Grooming">
                                            Local Pet Grooming
                                        </option>
                                        <option id="item47_95_option" value="Local Phone Repair">
                                            Local Phone Repair
                                        </option>
                                        <option id="item47_96_option" value="Local Plasterers">
                                            Local Plasterers
                                        </option>
                                        <option id="item47_97_option" value="Local Plumber">
                                            Local Plumber
                                        </option>
                                        <option id="item47_98_option" value="Local Printer">
                                            Local Printer
                                        </option>
                                        <option id="item47_99_option" value="Local Private Investigator">
                                            Local Private Investigator
                                        </option>
                                        <option id="item47_100_option" value="Local Private Jet Rental">
                                            Local Private Jet Rental
                                        </option>
                                        <option id="item47_101_option" value="Local Public Relations">
                                            Local Public Relations
                                        </option>
                                        <option id="item47_102_option" value="Local Radio">
                                            Local Radio
                                        </option>
                                        <option id="item47_103_option" value="Local Real Estate Agent">
                                            Local Real Estate Agent
                                        </option>
                                        <option id="item47_104_option" value="Local Roofers">
                                            Local Roofers
                                        </option>
                                        <option id="item47_105_option" value="Local Room Rental">
                                            Local Room Rental
                                        </option>
                                        <option id="item47_106_option" value="Safe Place Women Children Homeless">
                                            Local Safe Place Women Children Homeless
                                        </option>
                                        <option id="item47_107_option" value="Local Sales">
                                            Local Sales
                                        </option>
                                        <option id="item47_108_option" value="Local Security">
                                            Local Security
                                        </option>
                                        <option id="item47_109_option" value="Local Shoe Shine Service">
                                            Local Shoe Shine Service
                                        </option>
                                        <option id="item47_110_option" value="Local Sign Printer">
                                            Local Sign Printer
                                        </option>
                                        <option id="item47_111_option" value="Local Skydiving">
                                            Local Skydiving
                                        </option>
                                        <option id="item47_112_option" value="Local Sober Living Home">
                                            Local Sober Living Home
                                        </option>
                                        <option id="item47_113_option" value="Local Stone Masons">
                                            Local Stone Masons
                                        </option>
                                        <option id="item47_114_option" value="Local Stylist">
                                            Local Stylist
                                        </option>
                                        <option id="item47_115_option" value="Local Tailor">
                                            Local Tailor
                                        </option>
                                        <option id="item47_116_option" value="Local Tattoos">
                                            Local Tattoos
                                        </option>
                                        <option id="item47_117_option" value="Local Top NotchTech Support">
                                            Local Tech Support Top Notch
                                        </option>
                                        <option id="item47_118_option" value="Local TeleHealth">
                                            Local TeleHealth
                                        </option>
                                        <option id="item47_119_option" value="Local Tour Guide">
                                            Local Tour Guide
                                        </option>
                                        <option id="item47_120_option" value="Local Tow Truck Nashville TN andres-towing.com">
                                            Local Tow Truck Nashville TN andres-towing.com
                                        </option>
                                        <option id="item47_121_option" value="Local Trash Removal">
                                            Local Trash Removal
                                        </option>
                                        <option id="item47_122_option" value="Local Travel Consultant">
                                            Local Travel Consultant
                                        </option>
                                        <option id="item47_123_option" value="Local Tree Trimmer">
                                            Local Tree Trimmer
                                        </option>
                                        <option id="item47_124_option" value="Local Watch Repair">
                                            Local Watch Repair
                                        </option>
                                        <option id="item47_125_option" value="Local Weaver">
                                            Local Weaver
                                        </option>
                                        <option id="item47_126_option" value="Wedding Planner">
                                            Local Wedding Planner
                                        </option>
                                        <option id="item47_127_option" value="Welders">
                                            Local Welders
                                        </option>
                                        <option id="item47_128_option" value="Windshield Repair">
                                            Local Windshield Repair
                                        </option>
                                        <option id="item47_129_option" value="Local Window Washing">
                                            Local Window Washing
                                        </option>
                                        <option id="item47_130_option" value="Local Window Tinting">
                                            Local Window Tinting
                                        </option>
                                        <option id="item47_131_option" value="Local Wood Worker">
                                            Local Wood Worker
                                        </option>
                                        </select>
    </div>
                                        
    <div class="payment-mode-section container">
      <div class="form-group">
        <label><B>Payment Type | Calculate Fare</B> Button will bring up an easy to copy and paste fare estimate receipt based on 15+ year old regulated legal taxi cab fares. It also compares it to the illegal predatory 1960s-70s wages uber lyft apps... some how get away with paying. <B>Send</B> button generates downloadable .pdf</label>
        <select name="paymentMode" id="paymentMode" class="form-control">
                                        <!-- <select> -->
                                        <option value="American Express">
                                            American Express
                                        </option>
                                        <option value="Barter">
                                            Barter
                                        </option>
                                        <option selected value="Cash | airportrides@sats.pm =lightning address">
                                            Cash | airportrides@sats.pm =lightning address
                                        </option>
                                        <option value="crypto.taxicab.tech mp.money">
                                            crypto.taxicab.tech mp.money 
                                        </option>
                                        <option value="Community Bank Card">
                                            Community Bank Card
                                        </option>

                                        <option value="Debit">

                                            Debit

                                        </option>
                                        <option value="Discover">
                                            Discover
                                        </option>
                                        <option value="Digital Cash wallet.mp.money">
                                            Digital Cash wallet.mp.money
                                        </option>
                                        <option value="eGift Certificate">
                                            eGift Certificate
                                        </option>                                        
                                        <option value="Gift Card">
                                            Gift Card
                                        </option>
                                        <option value="Mastercard">
                                            Mastercard
                                        </option>

                                        <option value="Mobile Phone Reload Card Info">

                                            Mobile Phone Reload Card Info

                                        </option>
                                        <option value="Paypal">
                                            Paypal
                                        </option>
                                        <option value="Square">
                                            Square
                                        </option>

                                        <option value="pay.TopNotch.net">

                                            pay.TopNotch.net

                                        </option>
                                        <option value="Venmo">
                                            Venmo
                                        </option>
                                        <option value="Visa">
                                            Visa
                                        </option>
                                        
                                        <option value="Zelle">

                                            Zelle

                                        </option>
                                    </select>
      </div>
      
      <div class="form-group mb-0">
        <button type="button" name="FormSubmit" id="calculateFare" class="btn btn-primary">Calculate Fare</button>
      </div>
    </div>  
    <div class="row" style="display:none" id="fare-table-data">
      <div class="container fare-data-table" id="fare-data-table">
        <input type="button" class="btn" onclick="selectElementContents( document.getElementById('table') );" value="Copy to Clipboard">
        <table id="table" autosize="1" style="overflow: wrap;border:1px solid #000;width:100%;">
        </table>
      </div>
    </div>
    <div class="row">
    <div class="container">
      <div class="main-form col-md-12 col-lg-12 col-sm-12">
        <h5>Generate, Download, Email .pdf - DIRECT TO DENVER DRIVER & Yourself by clicking send. The 5 seconds it takes DENVER DRIVER to read the email is 20 times more info ride "share" apps provide in 10+ seconds, distracts less and doesn't attempt to deFRAUD your driver into working for free, predatory, illegal 1970s wages with games, tricks, manipulations brogrammed into the app by game developers, neuro scientists, and criminal executives (still works if below fields are left blank)</h5>
        <hr>
        <div class="form-group">
          <label>Rider Name</label>
          <input type="text" name="userName" class="form-control" class="form-control">
        </div>

        <div class="form-group">
          <label>Rider Email</label>
          <input type="email" name="userEmail" class="form-control">
        </div>

        <div class="form-group">
          <label>Rider Phone Number</label>
          <input type="text" name="userContact" class="form-control">
        </div>
        <div class="g-recaptcha form-group" data-sitekey="6LdD10kgAAAAAAlnTTrMRuaZMMXF4CIR89l4V_zK"></div><br/>
        <div class="form-group mb-0">
        <h2>Pay 777 SATs to message TopNotch</h2>
          <button type="submit" name="FormSubmit" class="btn btn-primary">Send</button>
        </div>
        <br>
      </div>
    </div>
    <pay-with-ln payment-request=""></pay-with-ln>
  </form>
  </div>
        </div>
    </div>
    <footer class="container mx-auto footer-section text-white">
             <div class="footer-logo d-flex justify-c000-center mb-2">
             <img src="./image/airportrides.today_at_gmail.jpg" class="ml-auto mr-auto" height="270" width="100%"/>
             </div>
             <img src="./image/2-options-airportridestoday.jpg" alt="Options" width="100%">

             <img src="./image/4.jpg" alt="Options" width="100%">

             <img src="./image/pay-topnotch.png" alt="Pay" width="100%">

             <img src="./image/faq-airportrides.today.jpg" alt="FAQ" width="100%">
<a href="http://www.denver.airportrides.today">
<br>STATES:</br>
<a href=https://taxicabdelivery.online>"Alabama"</a>, <a href=https://taxicabdelivery.online>"Alaska"</a>, <a href=https://taxicabdelivery.online>"Arizona"</a>, <a href=https://taxicabdelivery.online>"Arkansas"</a>, <a href=https://taxicabdelivery.online>"California"</a>, <a href=https://taxicabdelivery.online>"Colorado"</a>, <a href=https://taxicabdelivery.online>"Connecticut"</a>, <a href=https://taxicabdelivery.online>"Delaware"</a>, <a href=https://taxicabdelivery.online>"Florida"</a>, <a href=https://taxicabdelivery.online>"Georgia"</a>, <a href=https://taxicabdelivery.online>"Hawaii"</a>, <a href=https://taxicabdelivery.online>"Idaho"</a>, <a href=https://taxicabdelivery.online>"Illinois"</a>, <a href=https://taxicabdelivery.online>"Indiana"</a>, <a href=https://taxicabdelivery.online>"Iowa"</a>, <a href=https://taxicabdelivery.online>"Kansas"</a>, <a href=https://taxicabdelivery.online>"Kentucky"</a>, <a href=https://taxicabdelivery.online>"Louisiana"</a>, <a href=https://taxicabdelivery.online>"Maine"</a>, <a href=https://taxicabdelivery.online>"Maryland"</a>, <a href=https://taxicabdelivery.online>"Massachusetts"</a>, <a href=https://taxicabdelivery.online>"Michigan"</a>, <a href=https://taxicabdelivery.online>"Minnesota"</a>, <a href=https://taxicabdelivery.online>"Mississippi"</a>, <a href=https://taxicabdelivery.online>"Missouri"</a>, <a href=https://taxicabdelivery.online>"Montana"</a>, <a href=https://taxicabdelivery.online>"Nebraska"</a>, <a href=https://taxicabdelivery.online>"Nevada"</a>, <a href=https://taxicabdelivery.online>"New Hampshire"</a>, <a href=https://taxicabdelivery.online>"New Jersey"</a>, <a href=https://taxicabdelivery.online>"New Mexico"</a>, <a href=https://taxicabdelivery.online>"New York"</a>, <a href=https://taxicabdelivery.online>"North Carolina"</a>, <a href=https://taxicabdelivery.online>"North Dakota"</a>, <a href=https://taxicabdelivery.online>"Ohio"</a>, <a href=https://taxicabdelivery.online>"Oklahoma"</a>, <a href=https://taxicabdelivery.online>"Oregon"</a>, <a href=https://taxicabdelivery.online>"Pennsylvania"</a>, <a href=https://taxicabdelivery.online>"Rhode Island"</a>, <a href=https://taxicabdelivery.online>"South Carolina"</a>, <a href=https://taxicabdelivery.online>"South Dakota"</a>,  <a href=https://taxicabdelivery.online>"Tennessee"</a>, <a href=https://taxicabdelivery.online>"Texas"</a>, <a href=https://taxicabdelivery.online>"Utah"</a>, <a href=https://taxicabdelivery.online>"Vermont"</a>, <a href=https://taxicabdelivery.online>"Virginia"</a>, <a href=https://taxicabdelivery.online>"Washington"</a>, <a href=https://taxicabdelivery.online>"Washington DC"</a>, <a href=https://taxicabdelivery.online>"West Virginia"</a>, <a href=https://taxicabdelivery.online>"Wisconsin"</a>, <a href=https://taxicabdelivery.online>"Wyoming"</a>
</a><br></br>
</p>
<p><a href="http://www.denver.airportrides.today">
DENVER
</a>
</p>
<p>
uber alternative, lyft alternative, uber eats alternative, doordash alternative, grubhub alternative, postmates alternative, instacart alternative, ride "share" / gig app web clone...
</p>


<p style="margin-top: 0; margin-bottom: 0;">Direct Links To Top 500 Most Populous USA Cities / Zip Codes</p>
<p style="margin-top: 0; margin-bottom: 0;">SHORTCUT add your zip code.php to end of domain, for example https://taxicabdelivery.online/2022/<B>90210.php</B> to see if your city activated.</p>
    
<br><a href="https://taxicabdelivery.online/2022/lite.php">yourcityhere (blank version of this page ready for your gui, branding....) Claim or activate your city or zip code.</a></br>
<br><a href="https://taxicabdelivery.online/2022/index.php">Select nearest city or zip code for most accurate dead mile estimates. pay.topnotch.net $21 to add or list your city/zip code. (RIDERS can use any page they all work the same, dead mile estimates will not be accurate. Only needed for drivers tax records.)</a></br>

AL
</li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/alabama/airportrides/index.php">alabama</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://birmingham.airportrides.today">birmingham.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/35212.php">birmingham 35212</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://huntsville.airportrides.today">huntsville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/35824.php">huntsville 35824</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">alabama menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/alabama/menus/index.php">alabama menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mobile.airportrides.today">mobile.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/36605.php">mobile 36605</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://montgomery.airportrides.today">montgomery.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/36043.php">montgomery 36043</a></li>

AK
</li>	<li class="cat-item cat-item-000"><a href="http://alaska.airportrides.today">alaska.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://anchorage.airportrides.today">anchorage.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/99519.php">anchorage 99519</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/alaska/menus/index.php">alaska menus</a></li>

AZ
</li>	<li class="cat-item cat-item-000"><a href="http://arizona.airportrides.today">arizona.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/arizona/airportrides/index.php">arizona</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://chandler.airportrides.today">chandler.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85249.php">chandler 85249</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gilbert.airportrides.today">gilbert.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85233.php">gilbert 85233</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://glendale.airportrides.today">glendale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85307.php">glendale 85307</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/arizona/menus/index.php">arizona menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mesa.airportrides.today">mesa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85212.php">mesa 85212</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://peoria.airportrides.today">peoria.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85383.php">peoria 85383</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://phoenix.airportrides.today">phoenix.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85034.php">phoenix 85034</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://scottsdale.airportrides.today">scottsdale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85260.php">scottsdale 85260</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://surprise.airportrides.today">surprise.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85379.php">surprise 85379</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tempe.airportrides.today">tempe.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85282.php">tempe 85282</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tucson.airportrides.today">tucson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/85756.php">tucson 85756</a></li>

AR
</li>	<li class="cat-item cat-item-000"><a href="http://arkansas.airportrides.today">arkansas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/arkansas/airportrides/index.php">arkansas</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://littlerock.airportrides.today">littlerock.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/72202.php">littlerock 72202</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/arkansas/menus/index.php">arkansas menus</a></li>


CA
</li>	<li class="cat-item cat-item-000"><a href="http://california.airportrides.today">california.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/california/airportrides/index.php">california</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://anaheim.airportrides.today">anaheim.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92707.php">anaheim 92707</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://antioch.airportrides.today">antioch.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94509.php">antioch 94509</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bakersfield.airportrides.today">bakersfield.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93311.php">bakersfield 93311</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://berkeley.airportrides.today">berkeley.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94720.php">berkeley 94720</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://burbank.airportrides.today">burbank.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91505.php">burbank 91505</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://chulavista.airportrides.today">chulavista.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91909.php">chulavista 91909</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://concord.airportrides.today">concord.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94520.php">concord 94520</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://corona.airportrides.today">corona.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92879.php">corona 92879</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://costamesa.airportrides.today">costamesa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92626.php">costamesa 92626</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://dalycity.airportrides.today">dalycity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94015.php">dalycity 94015</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://downey.airportrides.today">downey.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90241.php">downey 90241</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elkgrove.airportrides.today">elkgrove.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95758.php">elkgrove 95758</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elmonte.airportrides.today">elmonte.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91731.php">elmonte 91731</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://escondido.airportrides.today">escondido.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92025.php">escondido 92025</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fontana.airportrides.today">fontana.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92335.php">fontana 92335</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fremont.airportrides.today">fremont.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94538.php">fremont 94538</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fresno.airportrides.today">fresno.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93727.php">fresno 93727</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fullerton.airportrides.today">fullerton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92833.php">fullerton 92833</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gardengrove.airportrides.today">gardengrove.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92840.php">gardengrove 92840</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://glendale.airportrides.today">glendale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91210.php">glendale 91210</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hayward.airportrides.today">hayward.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94542.php">hayward 94542</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://huntingtonbeach.airportrides.today">huntingtonbeach.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92646.php">huntingtonbeach 92646</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://inglewood.airportrides.today">inglewood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90301.php">inglewood 90301</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://irvine.airportrides.today">irvine.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92618.php">irvine 92618</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://la.airportrides.today">la</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90115.php">la 90015</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lancaster.airportrides.today">lancaster.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93550.php">lancaster 93550</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://longbeach.airportrides.today">longbeach.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90840.php">longbeach 90840</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://losangeles.airportrides.today">losangeles.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90043.php">losangeles 90043</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/california/menus/index.php"california menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://modesto.airportrides.today">modesto.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95354.php">modesto 95354</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://morenovalley.airportrides.today">morenovalley</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92571.php">morenovalley 92571</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://murrieta.airportrides.today">murrieta.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92562.php">murrieta 92562</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://norwalk.airportrides.today">norwalk.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90650.php">norwalk 90650</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://oakland.airportrides.today">oakland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94621.php">oakland 94621</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://oceanside.airportrides.today">oceanside.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/92008.php">oceanside 92008</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://ontario.airportrides.today">ontario.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91764.php">ontario 91764</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://orange.airportrides.today">orange.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92865.php">orange 92865</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://oxnard.airportrides.today">oxnard.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93036.php">oxnard 93036</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://palmdale.airportrides.today">palmdale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93550.php">palmdale 93550</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pasadena.airportrides.today">pasadena.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91105.php">pasadena 91105</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pomona.airportrides.today">pomona.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91763.php">pomona 91763</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://ranchocucamonga.airportrides.today">ranchocucamonga.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91739.php">ranchocucamonga 91739</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://richmond.airportrides.today">richmond.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94801.php">richmond 94801</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://riverside.airportrides.today">riverside.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92521.php">riverside 92521</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://roseville.airportrides.today">roseville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95661.php">roseville 95661</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sacramento.airportrides.today">sacramento.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95837.php">sacramento 95837</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://salinas.airportrides.today">salinas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93905.php">salinas 93905</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sanbernardino.airportrides.today">sanbernardino.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92405.php">sanbernardino 92405</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sanbuenaventura.airportrides.today">sanbuenaventura.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93003.php">sanbuenaventura 93003</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sandiego.airportrides.today">sandiego.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92101.php">sandiego 92101</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sanfrancisco.airportrides.today">sanfrancisco.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94218.php">sanfrancisco 94218</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sanjose.airportrides.today">sanjose.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95110.php">sanjose 95110</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://santaana.airportrides.today">santaana.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92707.php">santaana 92707</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://santaclara.airportrides.today">santaclara.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95110.php">santaclara 95110</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://santaclarita.airportrides.today">santaclarita.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91321.php">santaclarita 91321</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://santarosa.airportrides.today">santarosa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95404.php">santarosa 95404</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://simivalley.airportrides.today">simivalley.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93065.php">simivalley 93065</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://stockton.airportrides.today">stockton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/95204.php">stockton 95204</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sunnyvale.airportrides.today">sunnyvale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94086.php">sunnyvale 94086</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://temecula.airportrides.today">temecula.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92592.php">temecula 92592</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://thousandoaks.airportrides.today">thousandoaks.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91360.php">thousandoaks 91360</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://torrance.airportrides.today">torrance.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/90505.php">torrance 90505</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://vallejo.airportrides.today">vallejo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/94591.php">vallejo 94591</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://victorville.airportrides.today">victorville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/92394.php">victorville 92394</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://visalia.airportrides.today">visalia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/93277.php">visalia 93277</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westcovina.airportrides.today">westcovina.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/91792.php">westcovina 91792</a></li>



CO
</li>	<li class="cat-item cat-item-000"><a href="http://colorado.airportrides.today">colorado.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/colorado/airportrides/index.php">colorado</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://arvada.airportrides.today">arvada.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80002.php">arvada 80002</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://aspen.airportrides.today">aspen.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/81611.php">aspen 81611</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://aurora.airportrides.today">aurora.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80115.php">aurora 80015</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://blackhawk.airportrides.today">blackhawk.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80422.php">blackhawk 80422</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://boulder.airportrides.today">boulder.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80304.php">boulder 80304</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brecknridge.airportrides.today">brecknridge.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80424.php">brecknridge 80424</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://castlerock.airportrides.today">castlerock.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80104.php">castlerock 80104</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://centennial.airportrides.today">centennial.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80112.php">centennial 80112</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://coloradosprings.airportrides.today">coloradosprings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80906.php">coloradosprings 80906</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://denver.airportrides.today">denver.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80202.php">denver 80202</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://dtc.airportrides.today">dtc.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80112.php">dtc 80112</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://englewood.airportrides.today">englewood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80112.php">englewood 80112</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fortcollins.airportrides.today">fortcollins.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80524.php">fortcollins 80524</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://golden.airportrides.today">golden.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80401.php">golden 80401</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lakewood.airportrides.today">lakewood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80227.php">lakewood 80227</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://littleton.airportrides.today">littleton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80128.php">littleton 80128</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://longmont.airportrides.today">longmont.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80501.php">longmont 80501</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/colorado/menus/index.php">colorado menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://monument.airportrides.today">monument.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80921.php">monument 80921</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://parker.airportrides.today">parker.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80134.php">parker 80134</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pueblo.airportrides.today">pueblo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/81001.php">pueblo 81001</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://thornton.airportrides.today">thornton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/80233.php">thornton 80233</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westminster.airportrides.today">westminster.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/80333.php">westminster 80333</a></li>


CT
</li>	<li class="cat-item cat-item-000"><a href="http://connecticut.airportrides.today">connecticut.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/connecticut/airportrides/index.php">connecticut</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bridgeport.airportrides.today">bridgeport.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06497.php">bridgeport 06497</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fairfield.airportrides.today">fairfield.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06824.php">fairfield 06824</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://farmington.airportrides.today">farmington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06110.php">farmington 06110</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://greenwich.airportrides.today">greenwich.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06830.php">greenwich 06830</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hartford.airportrides.today">hartford.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06103.php">hartford 06103</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/connecticut/menus/index.php">connecticut menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://newhaven.airportrides.today">newhaven.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06511.php">newhaven 06511</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://stamford.airportrides.today">stamford.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06901.php">stamford 06901</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://waterbury.airportrides.today">waterbury.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/06706.php">waterbury 06706</a></li>



DC
</li>	<li class="cat-item cat-item-000"><a href="http://dc.airportrides.today">dc.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/22202.php">dc 22202</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/dc/menus/index.php">dc</a></li>



DE
</li>	<li class="cat-item cat-item-000"><a href="http://delaware.airportrides.today">delaware.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/delaware/airportrides/index.php">delaware</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://dover.airportrides.today">dover.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/19901.php">dover 19901</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/delaware/menus/index.php">delaware menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wilmington.airportrides.today">wilmington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/19720.php">wilmington 19720</a></li>



FL
</li>	<li class="cat-item cat-item-000"><a href="http://florida.airportrides.today">florida.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/florida/airportrides/index.php">florida</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://capecoral.airportrides.today">capecoral.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33432.php">boca 33432</a>
</li>	<li class="cat-item cat-item-000"><a href="http://boca.airportrides.today">boca.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33913.php">capecoral 33913</a>
</li>	<li class="cat-item cat-item-000"><a href="http://clearwater.airportrides.today">clearwater.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33762.php">clearwater 33762</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://coralsprings.airportrides.today">coralsprings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33071.php">coralsprings 33071</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fortlauderdale.airportrides.today">fortlauderdale.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33315.php">fortlauderdale 33315</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gainesville.airportrides.today">gainesville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/32609.php">gainesville 32609</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hialeah.airportrides.today">hialeah</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33013.php">hialeah 33013</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hollywood.airportrides.today">hollywood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33009.php">hollywood 33009</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://jacksonville.airportrides.today">jacksonville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/32218.php">jacksonville 32218</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/florida/menus/index.php">florida menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://miami.airportrides.today">miami.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33126.php">miami 33126</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://miamigardens.airportrides.today">miamigardens.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33056.php">miamigardens 33056</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://miramar.airportrides.today">miramar.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33025.php">miramar 33025</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://orlando.airportrides.today">orlando.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/32827.php">orlando 32827</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://palmbay.airportrides.today">palmbay.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/32901.php">palmbay 32901</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pembrokepines.airportrides.today">pembrokepines.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33023.php">pembrokepines 33023</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://portsaintlucie.airportrides.today">portsaintlucie.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/34957.php">portsaintlucie 34957</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://stpetersburg.airportrides.today">stpetersburg.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33713.php">stpetersburg 33713</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tallahassee.airportrides.today">tallahassee.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/32310.php">tallahassee 32310</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tampa.airportrides.today">tampa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33607.php">tampa 33607</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westpalmbeach.airportrides.today">westpalmbeach.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/33406.php">westpalmbeach 33406</a></li>


GA
</li>	<li class="cat-item cat-item-000"><a href="http://georgia.airportrides.today">georgia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/georgia/airportrides/index.php">georgia</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://athens.airportrides.today">athens.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/30602.php">athens 30602</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://atlanta.airportrides.today">atlanta.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/30320.php">atlanta 30320</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://augusta.airportrides.today">augusta.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/30906.php">augusta 30906</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">georgia menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/georgia/menus/index.php">georgia menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://savannah.airportrides.today">savannah.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/31408.php">savannah 31408</a></li>





HI
</li>	<li class="cat-item cat-item-000"><a href="http://hawaii.airportrides.today">hawaii.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/96819.php">hawaii 96819</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://honolulu.airportrides.today">honolulu.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/96815.php">honolulu 96815</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">hawaii menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/hawaii/menus/index.php">hawaii menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://molokai.airportrides.today">molokai.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/96770.php">molokai 96770</a></li>



ID
</li>	<li class="cat-item cat-item-000"><a href="http://idaho.airportrides.today">idaho.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/idaho/airportrides/index.php">idaho</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://boise.airportrides.today">boise.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/83705.php">boise 83705</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">idaho menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/idaho/menus/index.php">idaho menus</a></li>





IL
</li>	<li class="cat-item cat-item-000"><a href="http://illinois.airportrides.today">illinois.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/illinois/airportrides/index.php">illinois</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://aurora.airportrides.today">aurora.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/60506.php">aurora 60506</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://chicago.airportrides.today">chicago.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/60666.php">chicago 60666</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elgin.airportrides.today">elgin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/60120.php">elgin 60120</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://joliet.airportrides.today">joliet.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/60432.php">joliet 60432</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">illinois menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/illinois/menus/index.php">illinois menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://naperville.airportrides.today">naperville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/60540.php">naperville 60540</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://peoria.airportrides.today">peoria.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/61604.php">peoria 61604</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rockford.airportrides.today">rockford.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/61109.php">rockford 61109</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://springfield.airportrides.today">springfield.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/62701.php">springfield 62701</a></li>


IN
</li>	<li class="cat-item cat-item-000"><a href="http://indiana.airportrides.today">indiana.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/indiana/airportrides/index.php">indiana</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://evansville.airportrides.today">evansville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/47708.php">evansville 47708</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fortwayne.airportrides.today">fortwayne.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/46802.php">fortwayne 46802</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://indianapolis.airportrides.today">indianapolis.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/46241.php">indianapolis 46241</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">indiana menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/indiana/menus/index.php">indiana menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://southbend.airportrides.today">southbend.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/46637.php">southbend 46637</a></li>



IA
</li>	<li class="cat-item cat-item-000"><a href="http://iowa.airportrides.today">iowa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/iowa/airportrides/index.php">iowa</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cedarrapids.airportrides.today">cedarrapids.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/52404.php">cedarrapids 52404</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://desmoines.airportrides.today">desmoines.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/50321.php">desmoines 50321</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">iowa menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/iowa/menus/index.php">iowa menus</a></li>



KS
</li>	<li class="cat-item cat-item-000"><a href="http://kansas.airportrides.today">kansas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/kansas/airportrides/index.php">kansas</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://kansascity.airportrides.today">kansascity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/64153.php">kansascity 64153</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">kansas menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/kansas/menus/index.php">kansas menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://olathe.airportrides.today">olathe.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/66062.php">olathe 66062</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://overlandpark.airportrides.today">overlandpark.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/66214.php">overlandpark 66214</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://topeka.airportrides.today">topeka.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/66619.php">topeka 66619</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wichita.airportrides.today">wichita.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/67209.php">wichita 67209</a></li>



KY
</li>	<li class="cat-item cat-item-000"><a href="http://kentucky.airportrides.today">kentucky.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/kentucky/airportrides/index.php">kentucky</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://louisville.airportrides.today">louisville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/40209.php">louisville 40209</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">kentucky menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/kentucky/menus/index.php">kentucky menus</a></li>



LA
</li>	<li class="cat-item cat-item-000"><a href="http://louisiana.airportrides.today">louisiana.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/louisiana/airportrides/index.php">louisiana</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://batonrouge.airportrides.today">batonrouge.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/70811.php">batonrouge 70811</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lafayette.airportrides.today">lafayette.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/70504.php">lafayette 70504</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">louisiana menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/louisiana/menus/index.php">louisiana menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://neworleans.airportrides.today">neworleans.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/70062.php">neworleans 70062</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://shreveport.airportrides.today">shreveport.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/71109.php">shreveport 71109</a></li>



ME
</li>	<li class="cat-item cat-item-000"><a href="http://maine.airportrides.today">maine.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/maine/airportrides/index.php">maine</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">maine menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/maine/menus/index.php">maine menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://portland.airportrides.today">portland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/04102.php">portland 04102</a></li>



MD
</li>	<li class="cat-item cat-item-000"><a href="http://maryland.airportrides.today">maryland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/maryland/airportrides/index.php">maryland</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://baltimore.airportrides.today">baltimore.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/21240.php">baltimore 21240</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">maryland menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/maryland/menus/index.php">maryland menus</a></li>



MA
</li>	<li class="cat-item cat-item-000"><a href="http://massachusetts.airportrides.today">massachusetts.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/massachusetts/airportrides/index.php">massachusetts</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://boston.airportrides.today">boston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/02128.php">boston 02128</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cambridge.airportrides.today">cambridge.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/02142.php">cambridge 02142</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lowell.airportrides.today">lowell.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/01854.php">lowell 01854</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">massachusetts menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/massachusetts/menus/index.php">massachusetts menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://springfield.airportrides.today">springfield.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/01105.php">springfield 01105</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://worcester.airportrides.today">worcester.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/01609.php">worcester 01609</a></li>



ALL USA Menus
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">ALL USA Menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/menus/index.php">ALL USA Menus</a></li>



MI
</li>	<li class="cat-item cat-item-000"><a href="http://michigan.airportrides.today">michigan.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/michigan/airportrides/index.php">michigan</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://annarbor.airportrides.today">annarbor.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48104.php">annarbor 48104</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://detroit.airportrides.today">detroit.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48174.php">detroit 48174</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://flint.airportrides.today">flint.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48502.php">flint 48502</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://grandrapids.airportrides.today">grandrapids.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/49503.php">grandrapids 49503</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lansing.airportrides.today">lansing.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48823.php">lansing 48823</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">michigan menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/michigan/menus/index.php">michigan menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sterlingheights.airportrides.today">sterlingheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48310.php">sterlingheights 48310</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://warren.airportrides.today">warren.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/48093.php">warren 48093</a></li>



MN
</li>	<li class="cat-item cat-item-000"><a href="http://minnesota.airportrides.today">minnesota.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/minnesota/airportrides/index.php">minnesota</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">minnesota menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/minnesota/menus/index.php">minnesota menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://minneapolis.airportrides.today">minneapolis.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/55450.php">minneapolis 55450</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://paisleypark.airportrides.today">paisleypark.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/55317.php">paisley park 55317</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://saintpaul.airportrides.today">saintpaul.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/55450.php">saintpaul 55450</a></li>



MS
</li>	<li class="cat-item cat-item-000"><a href="http://mississippi.airportrides.today">mississippi.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/mississippi/airportrides/index.php">mississippi</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://jackson.airportrides.today">jackson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/39208.php">jackson 39208</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/mississippi/menus/index.php">mississippi menus</a></li>



MO
</li>	<li class="cat-item cat-item-000"><a href="http://missouri.airportrides.today">missouri.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/missouri/airportrides/index.php">missouri</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://columbia.airportrides.today">columbia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/65201.php">columbia 65201</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://kansascity.airportrides.today">kansascity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/64153.php">kansascity 64153</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">missouri menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/missouri/menus/index.php">missouri menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://springfield.airportrides.today">springfield.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/65897.php">springfield 65897</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://stlouis.airportrides.today">stlouis.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/63145.php">stlouis 63145</a></li>



MT
</li>	<li class="cat-item cat-item-000"><a href="http://montana.airportrides.today">montana.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/montana/airportrides/index.php">montana</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://billings.airportrides.today">billings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/59105.php">billings 59105</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">montana menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/montana/menus/index.php">montana menus</a></li>



NE
</li>	<li class="cat-item cat-item-000"><a href="http://nebraska.airportrides.today">nebraska.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/nebraska/airportrides/index.php">nebraska</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">nebraska menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/nebraska/menus/index.php">nebraska menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://omaha.airportrides.today">omaha.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/68110.php">omaha 68110</a></li>



NV
</li>	<li class="cat-item cat-item-000"><a href="http://nevada.airportrides.today">nevada.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/nevada/airportrides/index.php">nevada</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://alliante.airportrides.today">alliante.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89084.php">alliante 89084</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://aria.airportrides.today">aria.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">aria 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bellagio.airportrides.today">bellagio.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">bellagio 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://caesarspalace.airportrides.today">caesarspalace.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">caesarspalace 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://carsoncity.airportrides.today">carsoncity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89706.php">carsoncity 89706</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://circuscircus.airportrides.today">circuscircus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">circuscircus 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://encore.airportrides.today">encore.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">encore 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://greenvalleyranch.airportrides.today">greenvalleyranch</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89052.php">greenvalleyranch 89052</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://henderson.airportrides.today">henderson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89052.php">henderson 89052</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lasvegas.airportrides.today">lasvegas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">lasvegas 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://laughlin.airportrides.today">laughlin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89029.php">laughlin 89029</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://luxor.airportrides.today">luxor.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">luxor 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mandalaybay.airportrides.today">mandalaybay.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">mandalaybay 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">nevada menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/nevada/menus/index.php">nevada menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mgmgrand.airportrides.today">mgmgrand.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">mgmgrand 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://montecarlo.airportrides.today">montecarlo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">montecarlo 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://northlasvegas.airportrides.today">northlasvegas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89030.php">northlasvegas 89030</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://orleans.airportrides.today">orleans.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">orleans 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://ovo.airportrides.today">ovo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">ovo 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pahrump.airportrides.today">pahrump.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89060.php">pahrump 89060</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://palazzo.airportrides.today">palazzo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">palazzo 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://palms.airportrides.today">palms.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">palms 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://redrock.airportrides.today">redrock.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89135.php">redrock 89135</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://reno.airportrides.today">reno.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89511.php">reno 89511</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rio.airportrides.today">rio.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">rio 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://samstown.airportrides.today">samstown.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89122.php">samstown 89122</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://santafestation.airportrides.today">santafestation.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89130.php">santafestation 89130</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://southpoint.airportrides.today">southpoint.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89183.php">southpoint 89183</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://springvalley.airportrides.today">springvalley.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89148.php">springvalley 89148</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://summerlin.airportrides.today">summerlin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89135.php">summerlin 89135</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sunsetstation.airportrides.today">sunsetstation.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89014.php">sunsetstation 89014</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://texasstation.airportrides.today">texasstation.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">texasstation 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://venetian.airportrides.today">venetian.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">venetian 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westgate.airportrides.today">westgate.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">westgate 89119</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wynn.airportrides.today">wynn.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/89119.php">wynn 89119</a></li>


NH
</li>	<li class="cat-item cat-item-000"><a href="http://newhampshire.airportrides.today">newhampshire.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newhampshire/airportrides/index.php">newhampshire</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://manchester.airportrides.today">manchester.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/03103.php">manchester 03103</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">newhampshire menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newhampshire/menus/index.php">newhampshire menus</a></li>


NJ
</li>	<li class="cat-item cat-item-000"><a href="http://newjersey.airportrides.today">newjersey.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newjersey/airportrides/index.php">newjersey</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://atlanticcity.airportrides.today">atlanticcity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/08234.php">atlanticcity 08234</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elizabeth.airportrides.today">elizabeth.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/07114.php">elizabeth 07114</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://jerseycity.airportrides.today">jerseycity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/07304.php">jerseycity 07304</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">newjersey menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newjersey/menus/index.php">newjersey menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://newark.airportrides.today">newark.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/07102.php">newark 07102</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://peterson.airportrides.today">peterson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/07503.php">peterson 07503</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://seasideheights.airportrides.today">seasideheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/08751.php">seasideheights 08751</a></li>



NM
</li>	<li class="cat-item cat-item-000"><a href="http://newmexico.airportrides.today">newmexico.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newmexico/airportrides/index.php">newmexico</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://albuquerque.airportrides.today">albuquerque.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/87106.php">albuquerque 87106</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newmexico/menus/index.php">newmexico menus</a></li>

NY
</li>	<li class="cat-item cat-item-000"><a href="http://newyork.airportrides.today">newyork.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newyork/airportrides/index.php">newyork</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bronx.airportrides.today">bronx.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/10460.php">bronx 10460</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brooklyn.airportrides.today">brooklyn.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/11216.php">brooklyn 11216</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brownsville.airportrides.today">brownsville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/11212.php">brownsville 11212</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://buffalo.airportrides.today">buffalo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/14204.php">buffalo 14204</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://manhattan.airportrides.today">manhattan.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/10036.php">manhattan 10036</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">newyork menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/newyork/menus/index.php">newyork menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://newyorkcity.airportrides.today">newyorkcity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/11430.php">newyorkcity 11430</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://nyc.airportrides.today">nyc</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/10039.php">nyc harlem 10039</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rochester.airportrides.today">rochester.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/14607.php">rochester 14607</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://statenisland.airportrides.today">statenisland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/10306.php">statenisland 10306</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://syracuse.airportrides.today">syracuse.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/13208.php">syracuse 13208</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://yonkers.airportrides.today">yonkers.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/10701.php">yonkers 10701</a></li>


NC
</li>	<li class="cat-item cat-item-000"><a href="http://northcarolina.airportrides.today">northcarolina.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/northcarolina/airportrides/index.php">northcarolina</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cary.airportrides.today">cary.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27511.php">cary 27511</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://charlotte.airportrides.today">charlotte.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/28208.php">charlotte 28208</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://durham.airportrides.today">durham.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27560.php">durham 27560</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fayetteville.airportrides.today">fayetteville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/28314.php">fayetteville 28314</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://greensboro.airportrides.today">greensboro.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27406.php">greensboro 27406</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://highpoint.airportrides.today">highpoint.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27262.php">highpoint 27262</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">northcarolina menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/northcarolina/menus/index.php">northcarolina menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://raleigh.airportrides.today">raleigh.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27560.php">raleigh 27560</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wilmington.airportrides.today">wilmington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/28405.php">wilmington 28405</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://winstonsalem.airportrides.today">winstonsalem.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/27104.php">winstonsalem 27104</a></li>



ND
</li>	<li class="cat-item cat-item-000"><a href="http://northdakota.airportrides.today">northdakota.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/northdakota/airportrides/index.php">northdakota</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fargo.airportrides.today">fargo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/58102.php">fargo 58102</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">northdakota menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/northdakota/menus/index.php">northdakota menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://williston.airportrides.today">williston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/58801.php">williston 58801</a></li>


OH
</li>	<li class="cat-item cat-item-000"><a href="http://ohio.airportrides.today">ohio.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/ohio/airportrides/index.php">ohio</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://akron.airportrides.today">akron.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44307.php">akron 44307</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bedford.airportrides.today">bedford.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44146.php">bedford 44146</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://berea.airportrides.today">berea.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44017.php">berea 44017</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bowlinggreen.airportrides.today">bowlinggreen.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/43402.php">bowlinggreen 43402</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brookpark.airportrides.today">brookpark.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44142.php">brookpark 44142</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://canton.airportrides.today">canton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44706.php">canton 44706</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cincinnati.airportrides.today">cincinnati.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/41048.php">cincinnati 41048</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cleveland.airportrides.today">cleveland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44114.php">cleveland 44114</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://clevelandheights.airportrides.today">clevelandheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44121.php">clevelandheights 44121</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://columbus.airportrides.today">columbus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/43219.php">columbus 43219</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cuyahogafalls.airportrides.today">cuyahogafalls.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44221.php">cuyahogafalls 44221</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://dayton.airportrides.today">dayton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/45469.php">dayton 45469</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://eastcleveland.airportrides.today">eastcleveland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44112.php">eastcleveland 44112</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elyria.airportrides.today">elyria.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44035.php">elyria 44035</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://erie.airportrides.today">erie.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44870.php">erie 44870</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://euclid.airportrides.today">euclid.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44123.php">euclid 44123</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://garfieldheights.airportrides.today">garfieldheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44125.php">garfieldheights 44125</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://glenwood.airportrides.today">glenwood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/43610.php">glenwood 43610</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hamilton.airportrides.today">hamilton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/45011.php">hamilton 45011</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://harvard.airportrides.today">harvard.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44128.php">harvard 44128</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://independence.airportrides.today">independence.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44131.php">independence 44131</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://kent.airportrides.today">kent.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44240.php">kent 44240</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://kinsman.airportrides.today">kinsman.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44105.php">kinsman 44105</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lakewood.airportrides.today">lakewood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44107.php">lakewood 44107</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lorain.airportrides.today">lorain.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44053.php">lorain 44053</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://medina.airportrides.today">medina.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44256.php">medina 44256</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mentor.airportrides.today">mentor.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44060.php">mentor 44060</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">ohio menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/ohio/menus/index.php">ohio menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mapleheights.airportrides.today">mapleheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44137.php">mapleheights 44137</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://middleburgheights.airportrides.today">middleburgheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44130.php">middleburgheights 44130</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://miles.airportrides.today">miles.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44128.php">miles 44128</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://northolmsted.airportrides.today">northolmsted.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44070.php">northolmsted 44070</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://northridgeville.airportrides.today">northridgeville</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44039.php">northridgeville 44039</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://northroyalton.airportrides.today">northroyalton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44133.php">northroyalton 44133</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://parma.airportrides.today">parma.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44219.php">parma 44129</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sandusky.airportrides.today">sandusky.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44870.php">sandusky 44870</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://shakerheights.airportrides.today">shakerheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44122.php">shakerheights 44122</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://solon.airportrides.today">solon.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44139.php">solon 44139</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://southingtontownship.airportrides.today">southingtontownship.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44470.php">southingtontownship 44470</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://stclair.airportrides.today">stclair.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44108.php">st clair 44108</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://strongsville.airportrides.today">strongsville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44136.php">strongsville 44136</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://superior.airportrides.today">superior.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44103.php">superior 44103</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://toledo.airportrides.today">toledo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/43605.php">toledo 43605</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://twinsburg.airportrides.today">twinsburg.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44087.php">twinsburg 44087</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://warren.airportrides.today">warren.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44483.php">warren 44483</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://warrensvilleheights.airportrides.today">warrensvilleheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44128.php">warrensvilleheights 44128</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westlake.airportrides.today">westlake.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/44145.php">westlake 44145</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://xenia.airportrides.today">xenia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/45385.php">xenia 45385</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://yellowsprings.airportrides.today">yellowsprings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/45837.php">yellowsprings 45837</a></li>

OK
</li>	<li class="cat-item cat-item-000"><a href="http://oklahoma.airportrides.today">oklahoma.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/oklahoma/airportrides/index.php">oklahoma</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">oklahoma menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/oklahoma/menus/index.php">oklahoma menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://norman.airportrides.today">norman.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/73069.php">norman 73069</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://oklahomacity.airportrides.today">oklahomacity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/73159.php">oklahomacity 73159</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tulsa.airportrides.today">tulsa.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/74115.php">tulsa 74115</a></li>



OR
</li>	<li class="cat-item cat-item-000"><a href="http://oregon.airportrides.today">oregon.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/oregon/airportrides/index.php">oregon</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://eugene.airportrides.today">eugene.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/97402.php">eugene 97402</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gresham.airportrides.today">gresham.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/97030.php">gresham 97030</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">oregon menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/oregon/menus/index.php">oregon menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://portland.airportrides.today">portland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/97218.php">portland 97218</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://salem.airportrides.today">salem.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/97301.php">salem 97301</a></li>


PA
</li>	<li class="cat-item cat-item-000"><a href="http://pennsylvania.airportrides.today">pennsylvania.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/pennsylvania/airportrides/index.php">pennsylvania</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://allentown.airportrides.today">allentown.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/18101.php">allentown 18101</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">pennsylvania menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/pennsylvania/menus/index.php">pennsylvania menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://philadelphia.airportrides.today">philadelphia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/19153.php">philadelphia 19153</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pittsburgh.airportrides.today">pittsburgh.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/15231.php">pittsburgh 15231</a></li>



RI
</li>	<li class="cat-item cat-item-000"><a href="http://rhodeisland.airportrides.today">rhodeisland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/rhodeisland/airportrides/index.php">rhodeisland</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">rhodeisland menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/rhodeisland/menus/index.php">rhodeisland menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://providence.airportrides.today">providence.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/02886.php">providence 02886</a></li>



SC
</li>	<li class="cat-item cat-item-000"><a href="http://southcarolina.airportrides.today">southcarolina.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/southcarolina/airportrides/index.php">southcarolina</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://charleston.airportrides.today">charleston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/29418.php">charleston 29418</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://columbia.airportrides.today">columbia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/29170.php">columbia 29170</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">southcarolina menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/southcarolina/menus/index.php">southcarolina menus</a></li>



SD
</li>	<li class="cat-item cat-item-000"><a href="http://southdakota.airportrides.today">southdakota.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/southdakota/airportrides/index.php">southdakota</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://aberdeen.airportrides.today">aberdeen.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/57401.php">aberdeen 57401</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brookings.airportrides.today">brookings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/57006.php">brookings 57006</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">southdakota menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/southdakota/menus/index.php">southdakota menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rapidcity.airportrides.today">rapidcity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/57701.php">rapidcity 57701</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://siouxfalls.airportrides.today">siouxfalls.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/57106.php">siouxfalls 57106</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://watertown.airportrides.today">watertown.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/57201.php">watertown 57201</a></li>



TN
</li>	<li class="cat-item cat-item-000"><a href="http://tennessee.airportrides.today">tennessee.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/tennessee/airportrides/index.php">tennessee</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://brentwood.airportrides.today">brentwood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37027.php">brentwood 37027</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://chattanooga.airportrides.today">chattanooga.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37402.php">chattanooga 37402</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://clarksville.airportrides.today">clarksville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37040.php">clarksville 37040</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://donelson.airportrides.today">donelson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37214.php">donelson 37214</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://franklin.airportrides.today">franklin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37064.php">franklin 37064</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gallatin.airportrides.today">gallatin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37066.php">gallatin 37066</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://henderson.airportrides.today">henderson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/38340.php">henderson 38340</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hermitage.airportrides.today">hermitage.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37076.php">hermitage 37076</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://jackson.airportrides.today">jackson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/38305.php">jackson 38305</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://knoxville.airportrides.today">knoxville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37914.php">knoxville 37914</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lebanon.airportrides.today">lebanon.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37087.php">lebanon 37087</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://memphis.airportrides.today">memphis.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/38116.php">memphis 38116</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">tennessee menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/tennessee/menus/index.php">tennessee menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mountjuliet.airportrides.today">mountjuliet.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37122.php">mountjuliet 37122</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://murfreesboro.airportrides.today">murfreesboro.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37129.php">murfreesboro 37129</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://nashville.airportrides.today">nashville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37214.php">nashville 37214</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://oldhickory.airportrides.today">oldhickory.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37138.php">oldhickory 37138</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://orangemound.airportrides.today">orangemound.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/38114.php">orangemound 38114</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://smyrna.airportrides.today">smyrna.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/37167.php">smyrna 37167</a></li>



TX
</li>	<li class="cat-item cat-item-000"><a href="http://texas.airportrides.today">texas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/texas/airportrides/index.php">texas</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://abilene.airportrides.today">abilene.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/79601.php">abilene 79601</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://amarillo.airportrides.today">amarillo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/79101.php">amarillo 79101</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://arlington.airportrides.today">arlington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/76015.php">arlington 76015</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://austin.airportrides.today">austin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78719.php">austin 78719</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://beaumont.airportrides.today">beaumont.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/77701.php">beaumont 77701</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://beecave.airportrides.today">beecave.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78738.php">beecave 78738</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://buda.airportrides.today">buda.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78610.php">buda 78610</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://carrollton.airportrides.today">carrollton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75006.php">carrollton 75006</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cedarpark.airportrides.today">cedarpark.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78613.php">cedarpark 78613</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://corpuschristi.airportrides.today">corpuschristi.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78415.php">corpuschristi 78415</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://dallas.airportrides.today">dallas.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75261.php">dallas 75261</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://denton.airportrides.today">denton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/76205.php">denton 76205</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://elpaso.airportrides.today">elpaso.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/79925.php">elpaso 79925</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fortworth.airportrides.today">fortworth.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/76102.php">fortworth 76102</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://fredericksburg.airportrides.today">fredericksburg.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78624.php">fredericksburg 78624</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://frisco.airportrides.today">frisco.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75034.php">frisco 75034</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://garland.airportrides.today">garland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75040.php">garland 75040</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://grandprairie.airportrides.today">grandprairie.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75052.php">grandprairie 75052</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hills.airportrides.today">hills.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78659.php">hills 78659</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://houston.airportrides.today">houston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/77032.php">houston 77032</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://irving.airportrides.today">irving.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75062.php">irving 75062</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://killeen.airportrides.today">killeen.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/76542.php">killeen 76542</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lakeway.airportrides.today">lakeway.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78734.php">lakeway 78734</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://laredo.airportrides.today">laredo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78041.php">laredo 78041</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://leander.airportrides.today">leander.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78641.php">leander 78641</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://lubbock.airportrides.today">lubbock.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/79401.php">lubbock 79401</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mcallen.airportrides.today">mcallen.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78501.php">mcallen 78501</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mckinney.airportrides.today">mckinney.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75070.php">mckinney 75070</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">texas menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/texas/menus/index.php">texas menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://mesquite.airportrides.today">mesquite.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75149.php">mesquite 75149</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://midland.airportrides.today">midland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/79705.php">midland 79705</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://pflugerville.airportrides.today">pflugerville.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78660.php">pflugerville 78660</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://plano.airportrides.today">plano.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/75074.php">plano 75074</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://portarthur.airportrides.today">portarthur.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/77640.php">portarthur 77640</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rollingwood.airportrides.today">rollingwood.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78746.php">rollingwood 78746</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://roundrock.airportrides.today">roundrock.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78665.php">roundrock 78665</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://sanantonio.airportrides.today">sanantonio.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78216.php">sanantonio 78216</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://waco.airportrides.today">waco.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78710.php">waco 78710</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westlakehills.airportrides.today">westlakehills.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/78746.php">westlakehills 78746</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wichitafalls.airportrides.today">wichitafalls.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/76308.php">wichitafalls 76308</a></li>


TopNotch
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/topnotch/index.php">TopNotch</a></li>


UT
</li>	<li class="cat-item cat-item-000"><a href="http://utah.airportrides.today">utah.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/utah/airportrides/index.php">utah</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">utah menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/utah/menus/index.php">utah menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://provo.airportrides.today">provo.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/84602.php">provo 84602</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://saltlakecity.airportrides.today">saltlakecity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/84122.php">saltlakecity 84122</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westvalleycity.airportrides.today">westvalleycity.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/84128.php">westvalleycity 84128</a></li>


VT
</li>	<li class="cat-item cat-item-000"><a href="http://vermont.airportrides.today">vermont.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/vermont/airportrides/index.php">vermont</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://burlington.airportrides.today">burlington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/05403.php">burlington 05403</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">vermont menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/vermont/menus/index.php">vermont menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rutland.airportrides.today">rutland.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/05701.php">rutland 05701</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://southburlington.airportrides.today">southburlington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/05403.php">southburlington 05403</a></li>


VA
</li>	<li class="cat-item cat-item-000"><a href="http://virginia.airportrides.today">virginia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/virginia/airportrides/index.php">virginia</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://alexandria.airportrides.today">alexandria.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/22302.php">alexandria 22302</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://arlington.airportrides.today">arlington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/22203.php">arlington 22203</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://chesapeake.airportrides.today">chesapeake.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23320.php">chesapeake 23320</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://hampton.airportrides.today">hampton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23666.php">hampton 23666</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://huntington.airportrides.today">huntington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/22303.php">huntington 22303</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">virginia menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/virginia/menus/index.php">virginia menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://newportnews.airportrides.today">newportnews.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23602.php">newportnews 23602</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://norfolk.airportrides.today">norfolk.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23518.php">norfolk 23518</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://richmond.airportrides.today">richmond.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23220.php">richmond 23220</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://virginiabeach.airportrides.today">virginiabeach.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/23453.php">virginiabeach 23453</a></li>


WA
</li>	<li class="cat-item cat-item-000"><a href="http://washington.airportrides.today">washington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/washington/airportrides/index.php">washington</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://bellevue.airportrides.today">bellevue.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/98005.php">bellevue 98005</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://everett.airportrides.today">everett.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/98203.php">everett 98203</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">washington menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/washington/menus/index.php">washington menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://seattle.airportrides.today">seattle.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/98158.php">seattle 98158</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://spokane.airportrides.today">spokane.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/99202.php">spokane 99202</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://tacoma.airportrides.today">tacoma.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/98158.php">tacoma 98158</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://vancouver.airportrides.today">vancouver.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/98664.php">vancouver 98664</a></li>


DC
</li>	<li class="cat-item cat-item-000"><a href="http://washingtondc.airportrides.today">washingtondc.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/20017.php">washingtondc 20017</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">washingtondc menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/washingtondc/menus/index.php">washingtondc menus</a></li>


WV
</li>	<li class="cat-item cat-item-000"><a href="http://westvirginia.airportrides.today">westvirginia.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/westvirginia/airportrides/index.php">westvirginia</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://charleston.airportrides.today">charleston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/25311.php">charleston 25311</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://huntington.airportrides.today">huntington.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/25704.php">huntington 25704</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">westvirginia menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/westvirginia/menus/index.php">westvirginia menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://morgantown.airportrides.today">morgantown.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/26505.php">morgantown 26505</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://parkersburg.airportrides.today">parkersburg.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/26101.php">parkersburg 26101</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://weirtonheights.airportrides.today">weirtonheights.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://https://taxicabdelivery.online/2022/26062.php">weirtonheights 26062</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://wheeling.airportrides.today">wheeling.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/26003.php">wheeling 26003</a></li>


WI
</li>	<li class="cat-item cat-item-000"><a href="http://wisconsin.airportrides.today">wisconsin.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/wisconsin/airportrides/index.php">wisconsin</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://greenbay.airportrides.today">greenbay.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/54313.php">greenbay 54313</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://madison.airportrides.today">madison.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/53704.php">madison 53704</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">wisconsin menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/wisconsin/menus/index.php">wisconsin menus</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://milwaukee.airportrides.today">milwaukee.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/53207.php">milwaukee 53207</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://westjordan.airportrides.today">westjordan.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/53207.php">westjordan</a></li>


WY
</li>	<li class="cat-item cat-item-000"><a href="http://wyoming.airportrides.today">wyoming.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/wyoming/airportrides/index.php">wyoming</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://casper.airportrides.today">casper.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82604.php">casper 82604</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cheyenne.airportrides.today">cheyenne.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82001.php">cheyenne 82001</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://cody.airportrides.today">cody.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82414.php">cody 82414</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://evanston.airportrides.today">evanston.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82930.php">evanston 82930</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://gillette.airportrides.today">gillette.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82718.php">gillette 82718</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://greenriver.airportrides.today">greenriver.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82935.php">greenriver 82935</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://jackson.airportrides.today">jackson.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/83002.php">jacksonhole 83002</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://laramie.airportrides.today">laramie.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82072.php">laramie 82072</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://menus.airportrides.today">wyoming menus.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/united/wyoming/menus/index.php">yourcityhere</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://riverton.airportrides.today">riverton.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82501.php">riverton 82501</a></li>
</li>	<li class="cat-item cat-item-000"><a href="http://rocksprings.airportrides.today">rocksprings.airportrides.today</a></li>
</li>	<li class="cat-item cat-item-000"><a href="https://taxicabdelivery.online/2022/82901.php">rocksprings 82901</a></li>

</li>
			</ul>



























<p>
DENVER DIA Flat Rates
<img src="./image/airportrides.today.jpg" alt="Denver DIA Flat Rates" width="100%">
</p>
Text FAQS:

<br>
<br>
There are 4 ways to use this web app.

<br>
<br>
<strong>1. FREE or pay.topnotch.net gladly accepting donations</strong> <br>This form uses google maps to calculate approximate miles & minutes of requested trip. It will round up, add a 5% gratuity and multiply that times 2004 taxi cab rates of $2 per mile .30 per minute.
<br>
</br>

<br>
When user hits "calculate fare", form will generate an easy to copy & paste estimate. When user hits "send" it automatically generates & downloads a transparent invoice / receipt in .pdf format and emails it to the driver(page owner) & email entered (rider). If Fields are left blank clicking "send" will still function by generating and downloading .pdf for future use. POPS.taxicab.tech or POPS.airportrides.today has an index of the top 3000 USA taxi cab destinations
</br>
</br>

<br>
The .pdf & email contain google maps links driver can use to drive to pick up address from their current location & another link that directs them to the drop off address. Links Open google maps, waze, or any desired map application the same way clicking the icon in the uber / lyft app does.
<br>
</br>

<br>
It contains a lot more information beneficial to ALL parties & only estimates using minimum legal wages from 2004. 
</br>
</br>

<br>
For redundancy, or if email is'nt working check spam folder or email / text the .pdf directly to local human Independent Contractor. Email and contact info should be at the top of the page. Most destinations & fares should'nt change, you can rename or save common or popular routes for unlimited uses.
<br>
</br>

<br>
Pre / Post Dead Miles Calculator is a great way to keep track of TOTAL ACTUAL COSTS for accounting & tax record keeping purposes.
</br>
Use your current status as an "Independent Contractor" to build your OWN client list. 

<br>There are local humans in your community that need a service without evil middlemen brogramming crime into an app, taking 50-90% of customer payment while paying illegal predatory 1970s wages removing money from the local community....
</br>

<br>
"If you need to schedule a future ride/delivery please visit www.mycity.airportrides.today or yourwebsite.com, you can still use app or we can cut out the criminal middlemen that's taking 50-90% of the fare & stalking us..."
</br>

<br>
Driver keeps 90+% of fare. 10 minimum rides per day $100 minimum gross per day. 

</br>
Similar earnings on sillyCON valley organized crime, RICO / labor law violating, wage theft, money laundering apps requires 20-30+ rides per day.

<br>
<br>

<br>
<br>
<strong>2. $21.00 pay.topnotch.net</strong> <br>
Add your info to code, Add Zip Code Profiles For Your Desired City...<a href="https://taxicabdelivery.online/united/menus/index.php">Menu to USA Menus Folder</a> <a href="https://taxicabdelivery.online/pops/index.php">pops.TaxiCab.tech Cheat Sheet</a>, AND to your State / City Folder.</br>
<br>
 <br>Add your Business, Email, Menu, Maps Link Address & More Info to Delivery Sections auto populated drop down list and Universal Copy & Paste Grocery Delivery List. Be part of the code in perpetuity (foreva eva).</br>
<br><a href="https://taxicabdelivery.online/pops/index.php">pops.taxicab.tech pops.roomservice.guru pops.airportrides.today</a> Popular Saved Taxi Cab Cheat Sheet for Rides & Average Fares Add Yours for $21</br>
<br><a href="https://taxicabdelivery.online/united/menus/index.php">menus.roomservice.guru menus.taxicab.tech menus.airportrides.today</a> USA Menus Directory Add Yours for $21</br>

</br>

<br>
<br>
<strong>3. $365.00 pay.topnotch.net</strong> per year.

<br>
Get available custom domain
www.yourcity.airportrides.today 

Your Email, Phone, City, Info will be added to your own page. 

Dispatch yourself & build your local book. 

At the end of every month pay $2 per ride you completed. 12 payments yearly. 

<br>
TLDR
$1 a day + $2 per ride yearly.
</br>

<br>
<br>
<strong>4. $777.77 pay.topnotch.net</strong> forever. Host paid till 2024 so forever means 10 years<br>
I send you all form files.

You obtain your own domain, upload folders via ftp, configure mail, contact settings, images, customize gui, port, etc.

Dispatch yourself & build your book.

At the end of every month pay 4% per ride you completed in perpetuity. (Foreva eva).


<br>
TLDR

$777.77 + 4% per ride in perpetuity payable at the end of every month.
</br>

<br>
I guess there is a 5th option & that's learn to code, hire a coder, get a google maps or similar api key & develop a similar app. It's not magic or rocket science it's basic math coupled with taxi cab rates.


Drivers & Riders negotiate preferred payment methods. CASH will always be king. It's suggested to offer gift certificates or let regular riders pay  $50-$100 deposit which goes towards future rides to reduce friction.
</br>

Payment & Account Gateway may be added in the future.

Support LOCAL Small Businesses, Farms, Independent Contractors & LEGAL wages.

Transparent receipts. No Games. No Fraud. Labor receives 90+% what clients pay.

Buy a friend...

If you try walking down a street towards where you need to go waving a $10 or higher bill with information about where you need to be dropped off like "5 miles north near..." you can probably get a ride in minutes while also supporting a local human. This complicated technique works at most gas stations, strip malls, parking lots, side streets in America, no app needed just some eye contact and a mouth. If you don't wish to use all the fancy tech you can just multiply $3 times the approx miles to get a good guestimate of what a legal fare would cost.

Rides, Deliveries, Chauffeurs, Private Drivers, Personal Shoppers cost more than a beer, pizza, burger.... Other options include friends, family members, co workers, bus passes, scooters, bikes, skateboardz, nike, adidas..... they certainly aren"t free or $3.99 unless you're into exploited labor. Adults work for legal wages, not stars, points, badges, bogo coupons. Work/Labor is NOT a game. No neuro scientists or game developers were used in creating this app to deFRAUD labor. Transparent Receipts.
<br>
If you can email or text you can DISPATCH yourself & build your own client list. Keep 100%
</br>
<br>
Universal Grocery Delivery Text List / Cart Coming Soon

If you can copy & paste, you can email or text a custom list for deliveries.
</p>
<div class="container-fluid">
  <div class="row">
    <div class="col-sm-6">
            <p>.mp3 4 Hours 4 Kingz</p>

      <p>.mp3 Player Clip 8GB</p>

      <p>.mp3 Player Clip 8GB + 128GB MicroSD Card</p>

      <p>128GB Flash Drive USB Key Chain</p>

      <p>32GB MicroSD Card</p>

      <p>128GB MicroSD Card</p>

      <p>32GB MicroSD USB Portable Media Player</p>

      <p>128GB MicroSD USB Portable Media Player</p>

      <p>32GB MicroSD Headphones</p>

      <p>128GB MicroSD Headphones</p>

      <p>128GB Classic Video Game Machine</p>

      <p>2.5" External Hard Drive 5TB</p>

      <p>256GB MicroSD Card</p>

      <p>3.5 mm aux Cable 1ft 3ft 6ft 10ft 15ft 25ft 50ft splitter</p>

      <p>3.5" External Hard Drive 10TB</p>

      <p>3.5" External Hard Drive 12TB</p>

      <p>3.5" External Hard Drive 14TB</p>

      <p>3.5" External Hard Drive 16TB</p>

      <p>3.5" External Hard Drive 18TB</p>

      <p>5 Gallon Bucket w Lid</p>

      <p>5 Gallon Bucket Brown Rice</p>

      <p>5 Gallon Bucket Dried Potatos</p>

      <p>5 Gallon Bucket Flour</p>

      <p>5 Gallon Bucket Sugar</p>

      <p>Agave</p>

      <p>Alfalfa Sprouts</p>

      <p>Almond Milk</p>

      <p>Almonds</p>

      <p>Apple</p>

      <p>Apricot</p>

      <p>Artichoke</p>

      <p>Asparagus</p>

      <p>Atemoya</p>

      <p>Avocado</p>

      <p>Baby Wipes Flushable</p>

      <p>Bacon</p>

      <p>Bamboo Shoots</p>

      <p>Banana Fresh Frozen Dried 3-10lbs</p>

      <p>Battery Power Bank| Slim | aaa | aa |</p>

      <p>Bean Sprouts</p>

      <p>Beans</p>

      <p>Beef Broth</p>

      <p>Beef Ground</p>

      <p>Beets</p>

      <p>Bell Peppers</p>

      <p>Bird Food</p>

      <p>Bison Buffalo</p>

      <p>Black Out Curtains</p>

      <p>Black Out Stickers</p>

      <p>Black Tea</p>

      <p>Blackberries Fresh Frozen Dried 3-10lbs</p>

      <p>Blueberries Fresh Frozen Dried 3-10lbs</p>

      <p>Bok Choy</p>

      <p>Bone Broth</p>

      <p>Boxers S M L</p>

      <p>Breakfast Burrito</p>

      <p>Broccoli Fresh Frozen Dried 3-10lbs</p>

      <p>Broom</p>

      <p>Brussels Sprouts</p>

      <p>Cabbage</p>

      <p>Candles</p>

      <p>Cantaloupe</p>

      <p>Carbon Fiber Heels | Replicas | ...</p>

      <p>Carrots Fresh Frozen Dried 3-10lbs</p>

      <p>Casaba Melon</p>

      <p>Cat Food</p>

      <p>Cauliflower</p>

      <p>CDs Tapes Vinyl Records 8 Tracks</p>

      <p>Celery Fresh Frozen Dried 3-10lbs</p>

      <p>Cell Phone 2021 Unlocked</p>

      <p>Cell Phone Battery Power Bank| Slim|</p>

      <p>Cell Phone Black Out Faraday Bag | Faraday Furniture Box</p>

      <p>Cell Phone Charging Cable 10ft15ft | 6ft 4in1 | 4ft 3in1 Retractable | 10ft USB C</p>

      <p>Cell Phone Message Encryption</p>

      <p>Cell Phone SIM Cards AT&T | T Mobile | Misc | Verizon</p>

      <p>CBD Balm Oil Tincture</p>

      <p>Cherries Fresh Frozen Dried 3-10lbs</p>

      <p>Chicken Breast Shredded</p>

      <p>Chicken Broth</p>

      <p>Chocolate Dark Powdered</p>

      <p>Cinnamon Spice</p>

      <p>Coconut Oil</p>

      <p>Coconuts</p>

      <p>Coffee * Big Face Coffee *</p>

      <p>Collard Greens</p>

      <p>Cologne</p>

      <p>Condoms Latex Non Latex XL XL NON Latex</p>

      <p>Cook Personal Chef</p>

      <p>Corn</p>

      <p>Corned Beef</p>

      <p>Cranberries Fresh Frozen Dried 3-10lbs</p>

      <p>Cucumber</p>

      <p>Cups</p>

      <p>Dates</p>

      <p>Dog Food</p>

      <p>Dried Plums</p>

      <p>DVDs</p>

      <p>Earphones</p>

      <p>Eggplant</p>

      <p>Encryption</p>

      <p>Essential Oils</p>

      <p>Ethernet Cable 1ft 3ft 7ft 10ft 14ft 20ft 30ft</p>

      <p>Fan</p>

      <p>Fennel</p>

      <p>Figs</p>

      <p>Fire Starter | Lighters | Bulk | Long | Matches |</p>

      <p>First Aid Kit</p>

      <p>Fish Food</p>

      <p>Garbage Bags 42 Gallon</p>

      <p>Garlic</p>

      <p>Garlic Spice</p>

      <p>Ginger Spice</p>

      <p>Goat</p>

      <p>Gooseberries</p>

      <p>Grape Seed Oil</p>

      <p>Grapefruit</p>

      <p>Grapes</p>

      <p>Green Beans</p>

      <p>Green Onions</p>

      <p>Green Tea</p>

      <p>Greens</p>

      <p>Guava</p>

      <p>Hand Sanatizer</p>

      <p>HDMI Cable 1.5ft 3ft 6ft 10ft 15ft 20ft 30ft</p>

      <p>Headphones</p>

      <p>Honeydew Melon</p>

      <p>Iceberg Lettuce</p>

      <p>Kale</p>

      <p>Kiwi fruit</p>

      <p>Kumquat</p>

      <p>Lamb</p>

      <p>Land ∞ Thee Picnic july 1-7 ∞</p>

      <p>Leeks</p>

      <p>Lemons</p>

      <p>Lettuce</p>

      <p>Lighters Bulk Long Electric Fire Starters Matches</p>

      <p>Lima Beans</p>

      <p>Limes</p>

      <p>Lockpicking Kit</p>

      <p>Madarins</p>

      <p>Mandarin Oranges</p>

      <p>Mangos Fresh Frozen Dried 3-10lbs</p>

      <p>Masks Neck Gaiters Bulk</p>

      <p>Mattress Twin Full Queen King California King</p>

      <p>Mattress Covers</p>

      <p>Microsd Card to USB Adapter</p>

      <p>Milk</p>

      <p>Modem</p>

      <p>Mop</p>

      <p>Mulberries</p>

      <p>Mushrooms</p>

      <p>Napa</p>

      <p>Nectarines</p>

      <p>Okra</p>

      <p>Olive Oil</p>

      <p>Onion</p>

      <p>Operating System OS FREE</p>

      <p>Oranges</p>

      <p>OTG Off The Grid Communications</p>

      <p>Papayas</p>

      <p>Paper Towels</p>

      <p>Parsnip</p>

      <p>Passion Fruit</p>

      <p>Peaches</p>

      <p>Peanut Butter</p>

      <p>Peanuts</p>

      <p>Pears</p>

      <p>Peas</p>

      <p>Pepper Spice</p>

      <p>Peppers</p>

      <p>Perfume</p>

      <p>Pi Hole</p>

      <p>Pillows</p>

      <p>Pineapple Chunks</p>

      <p>Pistachios</p>

      <p>Plantains</p>

      <p>Plums</p>

      <p>Pomegranate</p>

      <p>Poratable Power Station Solar Battery Generator 500x 6000x</p>

      <p>Power Strip 8 Outlets 6 Travel Strip</p>

      <p>Pork</p>

      <p>Potatoes</p>

      <p>PRO</p>

      <p>Production & Shows</p>

      <p>Projector</p>

      <p>Prunes</p>

      <p>Pumpkin</p>

      <p>Quince</p>

      <p>Radio No Agenda Gear | HAM | Software| Radio Emergency</p>

      <p>Radishes</p>

      <p>Raisins</p>

      <p>Raspberries</p>

      <p>Red Cabbage</p>

      <p>RFID Cloner</p>

      <p>Rhubarb</p>

      <p>Rice</p>

      <p>Romaine Lettuce</p>

      <p>Rutabaga</p>

      <p>Salmon Canned 1 2 Smoked Dried Source</p>

      <p>Salt Spice</p>

      <p>Sausage Breakfast</p>

      <p>Sausage Italian</p>

      <p>Shallots</p>

      <p>Sheets Twin Full Queen King California King</p>

      <p>Sherp</p>

      <p>Shoes</p>

      <p>Shoe Laces</p>

      <p>Silent Yacht</p>

      <p>Snow Peas</p>

      <p>Soap | Dish | Laundry |</p>

      <p>Socks Black White S M L</p>

      <p>23W Solar Phone Gadget Charger | 63W</p>

      <p>Solar Battery Portable Power Station Generator 500x 6000x</p>
      <p>Solar Charger 10 100 200</p>

      <p>Solar Portable Light</p>

      <p>Soy Milk</p>

      <p>Speaker Portable Mobile Waterproof</p>

      <p>Spinach</p>

      <p>Sprouts</p>

      <p>Squash</p>

      <p>Strawberries Fresh Frozen Dried 3-10lbs</p>

      <p>String Beans</p>

      <p>Sunglass Accessories Croakies</p>

      <p>Sunglasses Reflectables Anti Facial Recognition</p>

      <p>Sweet Potato</p>

      <p>T Shirts TopNotch</p>

      <p>Tangerines</p>

      <p>Tech Support TopNotch USA Based airportridestoday at gmail</p>

      <p>Tiny Hardware VPN Key Chain</p>

      <p>Toilet Paper</p>

      <p>Tomato Canned Sauce Paste</p>

      <p>Tools</p>

      <p>Tools</p>

      <p>Tools</p>

      <p>Tools</p>

      <p>Tools</p>

      <p>Tools</p>

      <p>Toothbrush Electric Water Pic</p>

      <p>TopNotch Custom VR Capable HTPCs Flight Simulators</p>

      <p>Towels Face Hand Body Beach</p>

      <p>Tumeric Spice</p>

      <p>TV</p>
      <p>USB Wall Charger 4 Ports</p>

      <p>Vacuum Cleaner</p>

      <p>VAN Life RV</p>

      <p>Vegetable Broth</p>

      <p>Video Projector</p>

      <p>Walnuts</p>

      <p>Water By The CaseWater Straw</p>

      <p>Watermelon</p>

      <p>Wine Top Of The Line...</p>

      <p>Wireless Router</p>

      <p>Yams</p>

      <p>Yellow Squash</p>

      <p>Zucchini Squash</p>

      <p>HARDWARE</p>
    </div>
    <div class="col-sm-6">
        <p>&nbsp;</p>

<p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://checkout.link/0c004">crypto accepted crypto.taxicab.tech</a></span></p>
<p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.host/bbq.mp3">.mp3 4 Hours 4 Kingz</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/8GB-SanDisk-Clip-Jam-MP3-Player-Pink/44995552?selected=true">.mp3 Player Clip 8GB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">.mp3 Player Clip 8GB + 128GB MicroSD Card</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">128GB Flash Drive USB Key Chain</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">128GB MicroSD Card</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/All-new-Kindle-Paperwhite-Waterproof-Storage/dp/B07745PV5G">32GB ebook reader</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/Samsung-Class-Micro-Adapter-MB-MC32GA/dp/B07NP96DX5">32GB MicroSD Card</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/Samsung-Class-Micro-Adapter-MB-MC32GA/dp/B07NP96DX5">32GB MicroSD Card - eBooks</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/32gb-languages.jpg">32GB MicroSD Card - Languages</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/32gb-black-history-micro-sd-card.jpg">32GB MicroSD Card - History</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/Denver 32GB MicroSD USB Media Player.jpg">32GB MicroSD USB Portable Media Player (100+ disc "DVD" "VHS" Player)</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/Player-Digital-Coaxial-Control-Keyboard/dp/B07WPY8VKL">128GB MicroSD USB Portable Media Player (100+ disc "DVD" "VHS" Player)</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/iJoy-Matte-Finish-Premium-Rechargeable-Wireless-Over-Ear-Bluetooth-Headphones-With-Mic/456843724">32GB MicroSD Headphones</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/Denver 32GB MicroSD USB Media Radio.jpg">32GB Radio MicroSD USB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/SUNhai-J19-Microphone-Hands-Free-Party-Black/dp/B085ND9MGM">32GB Radio</a></span></p> 
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/Waterproof-Bluetooth-Hands-Free-Activities-Gift-Black/dp/B089R3NP3R">32GB Speaker</a></span></p> 
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/Denver 128GB MicroSD Headphones.jpg">128GB MicroSD Headphones</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://taxicabdelivery.online/4sale/Denver 128GB Classic Video Game.jpg">128GB Classic Video Game Machine</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">2.5" External Hard Drive 5TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">256GB MicroSD Card</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.monoprice.com/product?p_id=30888">3.5 mm aux Cable 1ft</a> <a href="https://www.monoprice.com/product?p_id=18632">3ft</a> <a href="https://www.monoprice.com/product?p_id=30890">6ft</a> <a href="https://www.monoprice.com/product?p_id=30891">10ft</a> <a href="https://www.monoprice.com/product?p_id=30892">15ft</a> <a href="https://www.monoprice.com/product?p_id=644">25ft</a> <a href="https://www.monoprice.com/product?p_id=644">50ft</a></span> <a href="https://www.monoprice.com/product?p_id=30897">splitter</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">3.5" External Hard Drive 10TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">3.5" External Hard Drive 12TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">3.5" External Hard Drive 14TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">3.5" External Hard Drive 16TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">3.5" External Hard Drive 18TB</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket w Lid</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket Brown Rice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket Dried Potatos</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket Flour</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket Sugar</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">5 Gallon Bucket w Lid</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.costco.com/kirkland-signature-organic-blue-agave,-36-oz,-2-count.product.100381407.html">Agave</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Alfalfa Sprouts</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Almond Milk</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/mm-almonds-3-lbs/prod22160276?xid=plp_product_1_1">Almonds</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Apples Dried</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Apricot</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Artichoke</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Asparagus</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Atemoya</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Avocado</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Baby Wipes Flushable</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bacon</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bamboo Shoots</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Banana Fresh Frozen Dried 3-10lbs</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B07PXMF52C">Battery Power Bank</a>| <a href="https://camelcamelcamel.com/product/B01A6L85CC">Slim</a> | <a href="https://www.walmart.com/browse/household-essentials/aaa-batteries/1115193_1076905_6635703">aaa</a> | <a href="https://www.walmart.com/browse/household-essentials/aa-batteries/1115193_1076905_4272101">aa</a> |</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bean Sprouts</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Beans</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/mm-org-beef-brth-6pk-6-pk-32-oz/prod22460763?xid=plp_product_1_5">Beef Broth</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.kandccattle.com/kc-bundles">Beef Bulk Bitcoin Accepted kandccattle.com TX</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://1915farm.com">Beef Bulk Bitcoin Accepted 1915farm.com TX</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://brand2smeat.com/">Beef Bulk Bitcoin Accepted brand2smeat.com CO</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.fullcircle.farm/">Beef Bulk Bitcoin Accepted fullcircle.farm WI</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://holycowbeef.com/">Beef Bulk Bitcoin Accepted holycowbeef.com TX</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.keeganfilionfarm.com/">Beef Bulk Bitcoin Accepted keeganfilionfarm.com SC</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://markegardfamilyshop.com/">Beef Bulk Bitcoin Accepted markegardfamilyshop.com CA</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://ozarkprimebeef.com/">Beef Bulk Bitcoin Accepted ozarkprimebeef.com MO</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://oremfarms.com/">Beef Bulk Bitcoin Accepted oremfarms.com IN</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.thelogcabinranch.com/">Beef Bulk Bitcoin Accepted thelogcabinranch.com IL</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://watsonfarmsbeef.com/">Beef Bulk Bitcoin Accepted watsonfarmsbeef.com SC</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://weylfarms.com">Beef Bulk Bitcoin Accepted weylfarms.com AR</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://wrich-ranches.business.site/">Beef Bulk Bitcoin Accepted wrich-ranches.business.site CO</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://beefinitiative.com/the-beef-box">Beef Bulk Bitcoin Accepted beefinitiative.com</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Beef Ground</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Beets</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bell Peppers</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bird Food</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://rockriverbison.com/">Bison</a></span> <a href="https://rockriverbison.com/">Buffalo</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B073TZBBQK?context=search">Black Out</a> <a href="https://camelcamelcamel.com/product/B07KNPPGZT">Curtains</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.lightdims.com/store.htm">Black Out Stickers</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Black Tea</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Blackberries Fresh Frozen Dried 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Blueberries Fresh Frozen <a href="https://www.sunsetvalleyorganics.com/Product/Detail/ten-pound-dried/">Dried</a> 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bok Choy</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bone Broth</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.taxicabdelivery.online/4sale/bookmarks.html">bookmarks.html</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.costco.com/kirkland-signature-men's-boxer-brief,-4-pack.product.100323810.html">Boxers S M L</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://amigosmexicanfood.weebly.com/">Breakfast Burrito</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Broccoli Fresh Frozen Dried 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Broom</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Brussels Sprouts</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Bullet Proof Tees</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://twitter.com/TopNotch/status/56365446148009984">Buy The Block</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cabbage</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ourowncandlecompany.com/">Candles</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cantaloupe</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Carbon Fiber Heels | Replicas | ...</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Carrots Fresh Frozen Dried 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Casaba Melon</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.costco.com/kirkland-signature-healthy-weight-cat-food-20-lbs..product.100245775.html">Cat Food</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cauliflower</span></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">CDs</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Celery Fresh Frozen Dried 3-10lbs</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.bestbuy.com/site/motorola-moto-g-power-2021-unlocked-64gb-memory-flash-gray/6441178.p?skuId=6441178">Cell Phone 2021 Unlocked</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B07PXMF52C">Cell Phone Battery Power Bank</a>| <a href="https://camelcamelcamel.com/product/B01A6L85CC">Slim</a>|</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B06WGPLTVF">Cell Phone Black Out Faraday Bag</a> | Faraday Furniture Box </span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B073PT6Q1P">Cell Phone Charging Cable 10ft</a><a href="https://camelcamelcamel.com/product/B01GEDOPR0">15ft</a></span> | <a href="https://camelcamelcamel.com/product/B088JRRBQT">6ft 4in1</a> | <a href="https://camelcamelcamel.com/product/B07WSMB3WF">4ft 3in1 Retractable</a> | <a href="https://camelcamelcamel.com/product/B01MZIPYPY">10ft USB C</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://signal.org/download/">Cell Phone Message Encryption</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.jammer-store.com/gps-blockers-jammers/">Cell Phone Jammers</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.target.com/p/at-38-t-prepaid-sim-card-kit-nano-blue/-/A-78860883">Cell Phone SIM Cards AT&T</a> | <a href="https://www.t-mobile.com/cell-phone/t-mobile-sim-card">T Mobile</a> | <a href="https://www.walmart.com/browse/cell-phones/sim-cards/1105910_1097404">Misc</a> | <a href="https://www.bestbuy.com/site/verizon-sim-starter-kit/6376567.p?skuId=6376567">Verizon</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/gp/product/B00ZZ6NW5E">Cell Phone Wired Internet USB C to Ethernet Adapter</a>|</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/gp/product/B00RM3KXAU">Cell Phone Wired Internet MicroUSB -B to Ethernet Adapter</a>|</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.lavenderblossoms.org/product/cbd-tincture/">CBD Balm Oil Tincture</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cherries Fresh Frozen <a href="https://www.wisconsinmade.com/no-sugar-added-tart-dried-cherries-2969/">Dried</a> 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Chicken Breast Shredded</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.costco.com/kirkland-signature-organic-chicken-stock,-32-fl-oz,-6-count.product.100334015.html">Chicken Broth</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Chocolate Dark</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cinnamon Spice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/members-mark-organic-virgin-coconut-oil-56oz/prod22041058?xid=plp_product_1_1">Coconut Oil</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Coconuts</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Coffee * Big Face * Latte Larry's </span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Collard Greens</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.fragrancex.com/">Cologne</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.condomdepot.com/">Condoms Latex Non Latex XL XL NON Latex</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cook Personal Chef</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Corn</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Corned Beef</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/gp/product/B003696236">Cot $30</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href=https://wildernesspoets.com/products/oregon-cranberries?variant=34692652294">Cranberries Fresh Frozen Dried 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://history.mb.money">CryptoBancc</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Cucumber</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.costco.com/kirkland-signature-chinet-18-oz-plastic-cup,-red,-240-count.product.100421211.html">Cups</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Dates</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.costco.com/kirkland-signature-nature's-domain-organic-chicken-%2526-pea-dog-food-30-lb..product.100155729.html">Dog Food</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Dried Plums</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">DVDs</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Earphones</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Eggplant</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://protonmail.com/signup">Email that does'nt stalk you @protonmail.com</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://signal.org/download/">Encryption</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://scented.com/bulk-fragrances/">Essential Oils</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.monoprice.com/product?p_id=27483">Ethernet Cable 1ft</a> <a href="https://www.monoprice.com/product?p_id=27483">3ft</a> <a href="https://www.monoprice.com/product?p_id=27483">7ft</a> <a href="https://www.monoprice.com/product?p_id=27483">10ft</a> <a href="https://www.monoprice.com/product?p_id=27483">14ft</a> <a href="https://www.monoprice.com/product?p_id=27483">20ft</a> <a href="https://www.monoprice.com/product?p_id=27483">30ft</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/Vornado-Vintage6-Metal-Air-Circulator-Fan-Black/267345916">Fan</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Fennel</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Figs</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Fire Starter | Lighters | Bulk | Long | <a href="https://camelcamelcamel.com/product/B00Y4TYJTQ">Matches</a></span> |</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B000069EYA">First Aid Kit</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Fish Food</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/gp/product/B07V7B2VJB">Fitness Bands</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.homedepot.com/p/HUSKY-42-Gal-Contractor-Bags-50-Count-HK42WC050B/202973825">Garbage Bags 42 Gallon</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.costco.com/kirkland-signature-minced-california-garlic,-48-oz.product.100334304.html">Garlic</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Garlic Spice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Ginger Spice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://fridayglass.com/current-work.html">Glass Art</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Goat</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://thegoonwiththespoon.com/">GoonWiththeSpoon.com Burritos Ice Cream Sausages</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Gooseberries</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/members-mark-organic-virgin-coconut-oil-56oz/prod22041058?xid=plp_product_1_1">Grape Seed Oil</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Grapefruit</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Grapes</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Green Beans</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Green Onions</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Green Tea</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Greens</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Guava</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Hand Sanatizer</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.monoprice.com/product?p_id=13779">HDMI Cable 1.5ft</a> <a href="https://www.monoprice.com/product?p_id=13779">3ft</a> <a href="https://www.monoprice.com/product?p_id=13779">6ft</a> <a href="https://www.monoprice.com/product?p_id=13779">10ft</a> <a href="https://www.monoprice.com/product?p_id=13779">15ft</a> <a href="https://www.monoprice.com/product?p_id=13779">20ft</a> <a href="https://www.monoprice.com/product?p_id=13779">30ft</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Headphones</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Honeydew Melon</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Iceberg Lettuce</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Internet Suitcase Server</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://ipfs.io/">https://ipfs</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Kale</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Kiwi fruit</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Kumquat</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Lamb</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.landwatch.com/Garfield-County-Colorado-Land-for-sale/pid/336087109">Land ∞ Thee Picnic july 1-7 ∞ </a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Leeks</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Lemons</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Lettuce</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Lighters Bulk Long Electric Fire Starters Matches</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Lima Beans</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Limes</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.scamstuff.com/collections/lock-picking">Lockpicking Kit</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Madarins</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Mandarin Oranges</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Mangos Fresh Frozen Dried 3-10lbs</span></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.hoorag.com/small-packs-big-savings/">Masks Neck Gaiters Bulk</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/Lucid-10-Dual-Layered-Gel-Memory-Foam-Mattress-Queen/47903235">Mattress Twin Full Queen</a> <a href="https://www.walmart.com/ip/Linenspa-Dreamer-12-Inch-Gel-Memory-Foam-Hybrid-Mattress-Queen/380185983">King California King</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/Original-Bed-Bug-Blocker-Zippered-Mattress-Cover-Protector/21387685?selected=true">Mattress Covers</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B07G5JV2B5">Microsd Card to USB Adapter</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Milk</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.newegg.com/motorola-mb8600-10-cable-modem/p/N82E16825390011?Description=motorola%20modem&amp;cm_re=motorola_modem-_-25-390-011-_-Product">Modem</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Mop</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Mulberries</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Mushrooms</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Napa</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Nectarines</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Okra</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/extra-virgin-olive-oil-3l/prod6930034?xid=pdp_carousel_rich-relevance.rr0_1">Olive Oil</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Onion</span></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.linuxmint.com/download.php">Operating System OS FREE</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Oranges</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://signal.org/download/">OTG Off The Grid Communications</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Papayas</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Paper Towels</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Parsnip</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Passion Fruit</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Peaches</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Peanut Butter</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Peanuts</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pears</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Peas</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pepper Spice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Peppers</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.fragrancex.com/">Perfume</a></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://github.com/thinkst/opencanary">Pi Canary</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://raspiblitz.org/">Pi Crypto Lightning Bit Coin Node Hosting Raspiblitz</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://getumbrel.com/#start">Pi Crypto Lightning Bit Coin Node Hosting Umbrel</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://pi-hole.net/t">Pi Hole</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.raspberrypi.org/products/raspberry-pi-keyboard-and-hub/">Pi Keyboard PC</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://kodi.wiki/view/Raspberry_Pi">Pi Media Center Kodi Xbmc</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://retropie.org.uk/">Pi Retro Gaming</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.raspberryturk.com/">Pi Turk Chess Robot</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pillows</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/pineapple-chunks-4pk-20oz-jars/prod3030169?xid=plp_product_1_1">Pineapple</a></span><a href="https://www.samsclub.com/p/pineapple-chunks-4pk-20oz-jars/prod3030169?xid=plp_product_1_1"> Chunks</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/shelled-pistachios-24-oz/prod1941832?xid=plp_product_1_2">Pistachios</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Plantains</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Plums</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://podcastindex.org/">Podcast 2.0 Index</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://podcasterwallet.com/">podcasterwallet.com</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pomegranate</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><span class="style1"><span style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://us.ecoflow.com/products/ecoflow-delta-pro-portable-power-station">Portable Power Portable Generator Station Generator</a></span><a href="https://ecoflow.com/products/ecoflow-delta-power-station"> 1300</a> 110w<br></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.monoprice.com/product?p_id=11146">Power Strip 8 Outlets</a> <a href="https://www.monoprice.com/product?p_id=11146">6</a> <a href="https://camelcamelcamel.com/product/B00EYCEZLA">Travel</a> <a href="https://camelcamelcamel.com/product/B07D25RSWX">Strip</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pork</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Potatoes</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.bestbuy.com/site/benq-home-gaming-1080p-dlp-projector-white-silver/5655540.p?skuId=5655540">Projector</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Prunes</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Pumpkin</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Quince</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://podfathergear.com">Radio No Agenda Gear | HAM | Software</a>| <a href="https://camelcamelcamel.com/product/B00CZDT30S">Radio Emergency</a></span></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Radishes</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Raisins</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Raspberries</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Red Cabbage</span></p>

        <p style="margin-top:0; margin-bottom: 0;"><a href="https://shop.hak5.org/products/keysy">RFID Cloner</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Rhubarb</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Rice</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.benfieldprecision.com/rifles">Rifles</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Romaine Lettuce</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Rutabaga</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.costco.com/kirkland-signature-wild-alaskan-pink-salmon,-6-oz,-6-count.product.100334750.html">Salmon Canned 1</a> <a href="https://www.samsclub.com/p/dc-atlantic-salmon-5-pk-7-oz/prod19911501">2</a> Smoked Dried Source</p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Salt Spice</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Sausage Breakfast</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Sausage Italian</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Shallots</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Sheets Twin Full Queen King California King</span></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://sherp.global/">Sherp</a></span></p>

         <p style="margin-top:0; margin-bottom: 0;"><a href="http://topnotch.net">Shoes</a></p>

        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.lacelab.com/collections/best-sellers">Shoe Laces</a></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.scamstuff.com/products/lace-escape">Shoe Lace Hand Cuff Key</a></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.silent-yachts.com/">Silent Yacht</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Snow Peas</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.etsy.com/shop/clevelandsoapworks">Soap Custom</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Soap | Dish | Laundry |</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Socks Black White S M L</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://camelcamelcamel.com/product/B012YUJJM8">23W Solar Phone Gadget Charger</a> | <a href="https://camelcamelcamel.com/product/B07CG7WZ7Q">63W</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://us.ecoflow.com/products/ecoflow-delta-pro-portable-power-station">Solar Battery Portable Power Station Generator</a></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/dp/B000J2Q0T4/">Solar Shower</a></span><a href="https://www.amazon.com/Camplux-Outdoor-Portable-Propane-Tankless/dp/B01CJPU6JI/ref=asc_df_B01CJPU6JI/"> Electric Shower</a><a href="https://www.staples.com/Chapin-Poly-Adjustable-Cone-Nozzle-Polyethylene-Multi-Purpose-Pressurized-Hand-Sprayer-48-oz/product_854022">Portable<br></p>
          <a href="https://www.goalzero.com/shop/solar-chargers/nomad-10w-solar-panel/">Solar Charger 10</a> <a href="https://www.bestbuy.com/site/goal-zero-nomad-100-portable-solar-panel-black/6409001.p?skuId=6409001">100</a> 200 </p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.bestbuy.com/site/goal-zero-crush-light-chroma-60-lumen-led-lantern-black-white/6370617.p?skuId=6370617">Solar Portable Light</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Soy Milk</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/Waterproof-Bluetooth-Hands-Free-Activities-Gift-Black/dp/B089R3NP3R">Speaker Portable Mobile Waterproof</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Spinach</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Sprouts</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Squash</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Strawberries Fresh Frozen Dried 3-10lbs</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">String Beans</span></p>

         <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.croakies.com/">Sunglass Accessories Croakies</a></span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.reflectacles.com/">Sunglasses Reflectables Anti Facial Recognition</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Sweet Potato</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">T Shirts TopNotch</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Tangerines</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">Tech Support TopNotch USA Based airportrides.today at gmail</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.tinyhardwarefirewall.com/">Tiny Hardware VPN Key Chain</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Toilet Paper</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Tomato Canned Sauce Paste</span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.target.com/p/oral-b-white-pro-crossaction-1000-rechargeable-electric-toothbrush/-/A-50838874">Toothbrush</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ifixit.com/Store/Tools/Toolkits">Tools Auto </a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ifixit.com/Store/Tools/Toolkits">Tools Construction</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ifixit.com/Store/Tools/Toolkits">Tools Hand</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.amazon.com/dp/B0007ZF4OA/">Tools Heat Handwarmers Disposable</a></span><a href="https://www.bedbathandbeyond.com/store/product/vornado-reg-velocity-3r-digital-whole-room-vortex-heater-in-black/5500270"> Portable Electric Heater</a> Rechargeable<br>Propane<br></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.polarbearcoolers.com/product/original-12-pack-soft-cooler/">Tools Cold Cooler Soft 12pack</a></span><a href="https://www.walmart.com/ip/Igloo-ICEB26HNBK-26-Pound-Automatic-Self-Cleaning-Portable-Countertop-Ice-Maker-Machine-With-Handle-Black/406640756"> Portable Ice Maker</a> Portable Freezer<br></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ifixit.com/Store/Tools/Toolkits">Tools Power</a></span></p>

        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.ifixit.com/Store/Tools/Toolkits">Tools Technology</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://topnotch.net">TopNotch Custom VR Capable HTPCs Flight Simulators</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Towels Face Hand Body</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Tumeric Spice</span></p>
        <p style="margin-top:0; margin-bottom: 0;"><a href="https://www.bestbuy.com/site/benq-home-gaming-1080p-dlp-projector-white-silver/5655540.p?skuId=5655540">TV</a><br>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Ultra Large Scale Docking Chemical Printer LSD</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Ultra Large Scale Docking Chemical Printer Mescalin</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Ultra Large Scale Docking Chemical Printer MDMA</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Ultra Large Scale Docking Chemical Printer Psilocin</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://research.unsw.edu.au/people/scientia-professor-veena-sahajwalla">Urban Micro Factory for eWaste Mining Recycling</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.monoprice.com/product?p_id=16242">USB Wall Charger 4 Ports</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.walmart.com/ip/Shark-Navigator-Lift-Away-Upright-Vacuum-NV351/15528564">Vacuum Cleaner</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.readysetvan.com/">VAN Life</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Vegetable Broth</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.bestbuy.com/site/benq-home-gaming-1080p-dlp-projector-white-silver/5655540.p?skuId=5655540">Video Projector</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Wallet | Lightning.Network | BlueWallet.io | BRD.com OnChain Bread | Strike.me Custodial | breez.technology phoenix.acinq.co Non Custodial | </span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.samsclub.com/p/mm-walnuts-3-lbs/prod22160277?xid=plp_product_1_1">Walnuts</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Water By The Case</span><a href="https://camelcamelcamel.com/product/B00FA2RLX2">Water Straw</a></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Watermelon</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="http://weedshare.tech">WeedShare.tech</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://earlstevensselections.com/">Wine Top Of The Line...</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;"><a href="https://www.newegg.com/netgear-archer-a20/p/N82E16833704485?Description=tp-link%20ac4000%20router&amp;cm_re=tp-link_ac4000%20router-_-33-704-485-_-Product&amp;quicklink=true">Wireless Router</a></span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Yams</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Yellow Squash</span></p>
        <p style="margin-top:0;margin-bottom:0;"><span style="font-family:Times New Roman;font-size:15px;">Zucchini Squash</span></p>

<p>HARDWARE</p>
<p>TLDR - FREE.taxicab.tech web app/form uses the Google maps API to calculate 2004 legal regulated taxi cab rates of $2 per mile & .30 per minute, then adds a 5% gratuity. The Driver or Labor keeps 90+% of the fare. No middleman needed.
Uber Lyft pay 1960s-1974 taxi cab rates of .60 per mile & .10 per minute GROSS. 90+% of the requests Uber Lyft sends ARE Labor (Human) Trafficking as they attempt and or succeed in DeFRAUDing labor into working for free or illegal wages by hiding contract details labor needs to do due diligence. Uber Lyft also take 50-90% of customers payment. 
This form duplicates hundreds of BILLIONS "worth" of sillyCON valley "technology". It requires no algorithms, no machine learning, no neuro scientists or game developers, there is no FRAUD brogrammed into this web app. It is FREE for anyone to use. It is not disruptive, innovative, or magic; it’s basic addition, multiplication, legal wages, & transparent receipts. Uber Lyft violate labor laws and human rights millions of times per day.
<a href="https://taxicabdelivery.online/pops/uber%20driver%20manifesto.txt">Uber Driver Manifesto</a> Free.taxicab.tech - a real time email dispatch system anyone can use for free or duplicate. 5 minute Ride or Delivery Requests via Email or text.</p>
</p>
<b>
</b>
<b>
</b> For more accurate dead mile estimates select your zip code / city from list above or use [V.2020] that lacks home destination zip code dead mile features located at <a href="https://taxicabdelivery.online/index2020.php">https://taxicabdelivery.online/index2020.php</a></p>
<p><img src="./image/now-hiring.jpg" alt="Now Hiring taxicab.tech roomservice.guru" width="100%"></p>
<p>Now Hiring $900 Sign Up Bonus! Requirements: *Commercial Insurance & Must be active on XL Tier of both uber & lyft platforms. *1000+ rides on least one 2+ years active on least 1. *Must give each rider provided business card and mention they can schedule future rides or deliveries via app or "TaxiCab.Tech | AirportRides.Today | RoomService.Guru | Weedshare.tech(optional)" *Ride requests sent via email 100% optional, don't reply within 5 minutes it's a cancel/ignore with NO penalties, threats, or games. $2 per mile .30 per minute. DRIVERS/LABOR keeps 90%. Just pay $2 per ride dispatched & accepted at the end of every month.</p>
 <p><img src="./image/uber-vs-labor-laws.jpg" alt="Help www.uberlyftishumantrafficking.me" width="100%"></p>
Pre-Teen boys in 1985 were paid $2 to deliver trash to the curb or a paper. Gig apps in 2021 offer it per hour millions of times per day to adult labor.
  <p><img src="./image/2dollars.jpg" alt="Help www.uberlyftishumantrafficking.me" width="100%"></p>
<p>pay.topnotch.net |-Software packages <a href="https://taxicabdelivery.online/4sale/">4sale</a> - $21 add info to code | $365 1 Year Dispatch, Hosting- $2 per ride | $777 10 Years Dispatch, Hosting- $2 per ride | 20K State/City Deployment | 1M Nationwide Deployment | <---Host & OWN your own TaxiCab Company for $365-1M--->[V.2021][cryptobancc-V.2022 launching soon]1234MO
 <p><img src="./image/1970s-betty-davis-taxicab.jpg" alt="Help www.uberlyftishumantrafficking.me" width="100%"></p>
 <p><img src="./image/email-ride-request.jpg" alt="Help www.uberlyftishumantrafficking.me" width="100%"></p>
<p>cash crypto credit debit pre paid egift accepted crypto.taxicab.tech</p>
 <p><img src="./image/qr-crypto.taxicab.tech-mobile-wallet.jpg" alt="cash crypto credit debit pre paid egift accepted" width="100%"></p>
<p><img src="./image/uber-95-2016.jpg" alt="help.uberlyftishumantrafficking.me" width="50%"></p>
<p>Scan QR Lightning Invoice To Pay For Denver Airport Rides</p>


  <p><a href="https://taxicabdelivery.online/pops/source.txt">View Clean Code</a></p>
  <p><a href="https://cryptostream.tech/video/how2-pay-wallet-dot-mp-dot-money-lightning-address-in-30-seconds.mp4">how2 pay with lightning</a>wallet.airportrides.today</p>
 </div>
  </div>
</div>
    </footer>
    <!-- Js Scripts -->
    <script src="./js/jquery-1.11.1.min.js"></script>
    <script src="./js/jquery.validate.min.js"></script>
    <script src="./js/additional-methods.min.js"></script>
    <script src="./js/popper.min.js"></script>
	<script src="./js/bootstrap.min.js"></script>
    <script src="./js/clipboard.min.js"></script>
<!-- custom javascript code -->
<script>
        $(document).ready(function() {
            google.maps.event.addDomListener(window, 'load', initialize);
            new Clipboard('#copy-table-button');
            $('#calculateFare').on('click', function(e){
                e.preventDefault();
                $('#fare-table-data').show();
                $.ajax({
                    type: 'post',
                    url: 'calculateFare.php',
                    data: $('#form').serialize(),
                    success: function(data){
                        $('#table').html(data);
                    }
                });
            });
        })

        function selectElementContents(el) {
            var body = document.body,
            range, sel;
            if (document.createRange && window.getSelection) {
            range = document.createRange();
            sel = window.getSelection();
            sel.removeAllRanges();
            range.selectNodeContents(el);
            sel.addRange(range);
            }
            document.execCommand("Copy");
        }

        function initialize() {
            var pickupLocation = document.getElementById('pickupLocation');
            var autocomplete = new google.maps.places.Autocomplete(pickupLocation);
            var dropLocation = document.getElementById('dropLocation');
            var autocomplete = new google.maps.places.Autocomplete(dropLocation);
        }
        function getLocation(){
            if (navigator.geolocation) {
                loc = navigator.geolocation.getCurrentPosition(success, error, options);
            }
        }
        var options = {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
        };

        function success(pos) {
        var crd = pos.coords;
        displayLocation(crd.latitude, crd.longitude);
        }

        function error(err) {
        console.warn(`ERROR(${err.code}): ${err.message}`);
        }
        function displayLocation(latitude,longitude){
        var request = new XMLHttpRequest();
        var method = 'GET';
        var url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='+latitude+','+longitude+'&sensor=true&key=AIzaSyC0BPzIIlQUNJgvge9EpSstUS4MbKPPSLo';
        var async = true;
        request.open(method, url, async);
        request.onreadystatechange = function(){
            console.log(request.status);
          if(request.readyState == 4 && request.status == 200){
            var data = JSON.parse(request.responseText);
            var address = data.results[0];
            $('#pickupLocation').val(address.formatted_address);
          }
        };
        request.send();
      };
      function validateForm(e) {
            if ($('#dateTime').val() == "") {
                alert("Date must be filled out");
                return false;
            }
            if ($('#majorLocation').val() == "") {
                alert("In or Near Location must be filled out");
                return false;
            }
            if ($('#pickupLocation').val() == "") {
                alert("Pickup must be filled out");
                return false;
            }
            if ($('#dropLocation').val() == "") {
                alert("Drop Location must be filled out");
                return false;
            }
            if ($('#paymentMode').val() == "") {
                alert("Payment must be filled out");
                return false;
            }
            if ($('#userName').val() == "") {
                alert("Name must be filled out");
                return false;
            }
            if ($('#userEmail').val() == "") {
                alert("Email must be filled out");
                return false;
            }
            if ($('#userContact').val() == "") {
                alert("Contact must be filled out");
                return false;
            }
            const pickupLocation              = $('#pickupLocation').val();
            const dropLocation                = $('#dropLocation').val();
            const q1                          = encodeURIComponent($.trim(pickupLocation).replace(/\r?\n/, ',').replace(/\s+/g, ' '));
            const q2                          = encodeURIComponent($.trim(dropLocation).replace(/\r?\n/, ',').replace(/\s+/g, ' '));
            $('#pickupLocationLink').val(q1);
            $('#dropLocationLink').val(q2);
            return true;
        }
    </script>

<script>
      var invoiceId; // To store the ID of the created invoice

      var settings = {
        url:
          "https://api.lnpay.co/v1/wallet/waki_ucUzLVZgePHqOGbEchxAa6E/invoice?fields=id,payment_request",
        method: "POST",
        timeout: 0,
        headers: {
          "Content-Type": "application/json",
          "X-Api-Key": "pak_g9017h233CD61lmB2PHaEGqBp1uSwh0",
          // ... other headers
          Cookie:
            "PHPSESSID=j54reph3e97lb7sqv75al1ed06; _csrf=d8f6ca057566d1fedf057e1470679d1d3811cf3a9f4172057a487aab281ddc4aa%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22QWI6VN_OuvaB-MkCL-H0cmvnDzhVq4lz%22%3B%7D"
        },
        data: JSON.stringify({
          num_satoshis: 777,
          memo: "Payment for messaging TopNotch"
        })
      };

      $.ajax(settings).done(function (response) {
        console.log(response);
        invoiceId = response.id; // Store the ID of the created invoice
        $("pay-with-ln").attr("payment-request", response.payment_request);
      });

      $("pay-with-ln").on("payment-confirmed", function () {
        // Before submitting the form, check if the payment has been made
        checkPaymentAndSubmitForm();
      });

      function checkPaymentAndSubmitForm() {
        // Use the GET /lntx/:id endpoint to check the status of the invoice
        $.ajax({
          url: "https://api.lnpay.co/v1/lntx/" + invoiceId,
          method: "GET",
          headers: {
            "X-Api-Key": "pak_g9017h233CD61lmB2PHaEGqBp1uSwh0"
            // ... other headers
          }
        }).done(function (response) {
          if (response.settled === 1) {
            // Check if the invoice is settled
            $("#messageForm").submit();
          } else {
            alert("Payment not confirmed. Please try again.");
          }
        });
      }
    </script>

</body>

</html>
