<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Process_files extends CI_Controller{

    public function __construct()
    {

        parent::__construct();

        $this->load->model('mantenimiento_modelo');

        $host = "192.168.10.221";

        $this->regiones = array(
            "AYACUCHO" => array(
                'subcarpeta' => 'dataACUS',
                'host' => $host,
            ),
        );

    }

    public function descargar_archivos($region){

        try {

            $nombreArchivo = 'TRAFICO_DD_'.date('Ymd') .'0000.ZIP';

            $fecha = date('Y-m-d');

            $region = strtoupper($region);

            $resp_region = $this->regiones["$region"];

            $host = $resp_region['host'];

            $subcarpeta = $resp_region['subcarpeta'];

            $directorio_archivo = directorio_archivos_zip.$subcarpeta."/"."$fecha/";

            $directorio_full = $directorio_archivo.$nombreArchivo;

            $path_source = "http://$host/$subcarpeta/$nombreArchivo";

            $fie_directory_exists = $this->create_directory($directorio_archivo);

            if(!$fie_directory_exists) throw new Exception('Directorio destino no existe');

            $archivo_descargado = file_get_contents($path_source);

            $file_was_created = $this->create_temporary_file($directorio_archivo, $nombreArchivo);

            if(!$file_was_created) throw new Exception('Error al crear archivo Zip');

            file_put_contents($directorio_archivo.$nombreArchivo, ($archivo_descargado));

            $is_descompress = $this->descompress_file($directorio_archivo.'descompress', $directorio_full);

            if(!$is_descompress) throw new Exception('Error al crear archivo Zip');

            $this->read_directory($directorio_archivo.'descompress/');

            log_message('error', ' archivo ===>'. "TERMINOOOO");

        } catch (Exception $e) {
            log_message('error', 'ERRO CATCH====> '. $e->getMessage());
        }
    }

    private function create_directory($file_directory){

        if (!file_exists($file_directory)) {

            mkdir($file_directory, 0777, true);

            if (!file_exists($file_directory)) {

                    return false;
                }
        }

        return true;

    }

    private function create_temporary_file($file_directory, $filename){

        $file_was_created = fopen($file_directory."$filename", 'w+');

        if($file_was_created == false){

            fclose($file_was_created);

            return false;

        }

        fclose($file_was_created);

        return true;

    }

    private function descompress_file($file_directory, $full_file_directory){

        $zip = new ZipArchive;

        if($zip->open($full_file_directory) === true){

            $zip->extractTo($file_directory);

            $zip->close();

            return true;

        }else{

            return false;

        }

    }

    public function read_directory($file_directory = './application/archivos_zip/dataACUS/2022-11-18/descompress/'){

        $status = true;

        if(!is_dir($file_directory)) $status =  false;

        $files = opendir($file_directory);

        while(($filename = readdir($files)) !== false){

            if($filename != '.' && $filename != '..'){

                $this->leer_archivo($file_directory, $filename);

            }

        }

        return $status;

    }

    private function leer_archivo($file_directory, $filename){

        $open_file = fopen($file_directory."$filename", 'r');

        $lineNumber = 1;

        while (($raw_string = fgets($open_file)) !== false) {

            $row_array = str_getcsv($raw_string);

            foreach($row_array as $row){

                $row_explode = explode(';', $row);

                $column_datetime = trim($row_explode[0]);
                $column_nodo = trim($row_explode[1]);
                $column_bytes_in = $this->convertBytesToMegaBytes(trim($row_explode[2]));
                $column_bytes_out = $this->convertBytesToMegaBytes(trim($row_explode[3]));
                
                // LLAMAR A UNA FUNCIÓN QUE PERMITE HACER EL REGISTRO EN UNA BASE DE DATOS

            }

            $lineNumber++;

        }

        fclose($open_file);
    }

    private function convertBytesToMegaBytes($bytes){

        return $formula = ($bytes * 8) / 1000000;
    }

}
