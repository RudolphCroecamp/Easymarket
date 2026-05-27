<?php
function deleteOracleProductImages($imagesToDelete)
{
    foreach ($imagesToDelete as $fileUrl) {

        error_log("Trying to delete: " . $fileUrl);

        $ch = curl_init($fileUrl);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log("Curl error: " . curl_error($ch));
        }

        error_log("HTTP CODE: " . $httpCode);
        error_log("Response: " . $response);

        curl_close($ch);

        if ($httpCode === 204 || $httpCode === 200) {
            error_log("Deleted: " . $fileUrl);
        } else {
            error_log("Failed delete: " . $fileUrl);
        }
    }

    return true;
}