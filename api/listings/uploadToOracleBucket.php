<?php
function uploadToOracle($filePath, $fileName)
{
    $parBaseUrl = "https://axjuhqtdpgv3.objectstorage.af-johannesburg-1.oci.customer-oci.com/p/DmvkjD8sU8N-IwgGrJMWB4vF9Y7ybsBZUa3W2DC0vmXaDCh4FlHDs1zWrg0-UlkT/n/axjuhqtdpgv3/b/easymarketBUcket/o/";

    $uploadUrl = $parBaseUrl . "products/" . $fileName;

    $fileData = file_get_contents($filePath);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);


    $ch = curl_init($uploadUrl);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: " . $mime,
        "Content-Length: " . strlen($fileData)
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception("Upload failed: " . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("Oracle upload failed with HTTP code: " . $httpCode);
    }

    return $fileName;
}



    //https://axjuhqtdpgv3.objectstorage.af-johannesburg-1.oci.customer-oci.com/p/DmvkjD8sU8N-IwgGrJMWB4vF9Y7ybsBZUa3W2DC0vmXaDCh4FlHDs1zWrg0-UlkT/n/axjuhqtdpgv3/b/easymarketBUcket/o/