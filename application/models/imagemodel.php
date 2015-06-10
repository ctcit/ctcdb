<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This class provides an interface to images in the database.
// Intended for use by the rest API, e.g. for TripReports.

define('THUMBWIDTH', 165);
define("THUMBNAIL_QUALITY", 90);

class Imagemodel extends CI_Model {
    public $id = 0;         // Image id (int)
    public $name = '';      // The name given to this image (usu. the orig filename)
    public $type = '';      // Image type (currently only jpeg supported)
    public $caption = '';   // Caption used to display it
    public $width = 0;      // Width in pixels
    public $height = 0;     // Height in pixels
    public $image = null;   // Image data (bytes, stored as Blob)
    public $t_width = 0;    // Thumbnail width
    public $t_height = 0;   // Thumbnail height
    public $thumb = null;   // Thumbnail data (bytes, also a Blob)
    
    public function create($name, $type, $caption, $image) {
        // Create an image given the main image info, from which thumbnail is
        // constructed.
        $this->name = $name;
        $this->type = strtolower($type);
        if ($this->type !== 'jpeg' && $this->type !== 'jpg') {
            throw new InvalidArgumentException("Image $name cannot be created. Type must be jpeg");
        }
        $this->image = $image;
        $this->caption = $caption;
        $tempnam = tempnam('/tmp', 'image');
        file_put_contents($tempnam, $image);
        $img = imagecreatefromjpeg($tempnam);
        if ($img === FALSE) {
            throw new RuntimeException("Attempt to create image $name failed");
        }
        $this->width = imagesx($img);
        $this->height = imagesy($img);
        
        // calculate thumbnail size
        $this->t_width = THUMBWIDTH;
        $this->t_height = floor( $this->height * ( THUMBWIDTH / $this->width ) );

        // create a new temporary image (empty)
        $thumb_img = imagecreatetruecolor( $this->t_width, $this->t_height );

        // copy and resize old image into new image 
        imagecopyresampled( $thumb_img, $img, 0, 0, 0, 0, $this->t_width,
                $this->t_height, $this->width, $this->height );

        // save thumbnail into a file
        $thumbname = tempnam('/tmp', 'image');
        if (imagejpeg( $thumb_img, $thumbname, THUMBNAIL_QUALITY ) === FALSE) {
            throw new RuntimeException("Failed to create thumbnail for $name");
        }
        $this->thumb = file_get_contents($thumbname);
        if ($this->db->insert('jos_image', $this) === FALSE) {
            throw new RuntimeException("Failed writing image $name to DB");
        }
        imagedestroy($thumb_img);
        imagedestroy($img);
        unlink($tempnam);
        unlink($thumbname);
        return $this->db->insert_id();
    }
    
    
    // Create an image with the given (file)name, the given
    // caption and the given dataurl (which must currently be jpeg).
    // The dataurl has the format 'data:image/jpeg;base64,abababbbcdde012 ...';
    // 
    // Return the id of the new image.
    public function create_from_dataurl($name, $caption, $dataurl) {
        list($type, $data) = explode(';', $dataurl);
        if (strtolower($type) !== 'data:image/jpeg') {
            throw new InvalidArgumentException("dataurl for image $name has unexpected format");
        }
        list($encoding, $data) = explode(',', $data);
        if (strtolower($encoding) !== 'base64') {
            throw new InvalidArgumentException("dataurl for image $name has unexpected format");
        }
        $image = base64_decode($data);
        $id = $this->create($name, 'jpeg', $caption, $image);
        return $id;
    }
    
    // Update just the name and caption of an image, given its id.
    public function update_name_and_caption($id, $name, $caption) {
        $this->db->where('id', $id);
        $this->db->update('jos_image', array('name'=>$name, 'caption'=>$caption));
    }
    
    public function delete($image_id) {
        $this->db->delete('jos_image', array('id'=>$image_id));
        $this->db->delete('jos_tripreport_image', array('image_id'=>$image_id));
    }
    
}