<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

use CodeIgniter\Entity;

class HutBooking extends Entity
{
    // ...
}

class HutBookingsModel extends Model
{
    // Use the 'bookings' database, ad defined in Config/Database.php
    protected $DBGroup = 'hutbooking';

    private const MaxBunks = 14;

    // We sneakily switch between the view and the underlying table as needed
    private const ViewTable = 'view_bookings';
    private const EditTable = 'bookings';

    protected $primaryKey = 'id';
    protected $allowedFields = [
        'member_id', 'start_date', 'nights', 'bunks',
        'status', 'notes', 'name', 'email', 'phone', 'door_code'
    ];
    protected $returnType = 'App\Models\HutBooking';
    protected $useTimestamps = true;
    protected $protectFields = true;

    protected $beforeInsert = ['SetEdit'];
    protected $beforeUpdate = ['SetEdit'];
    protected $beforeInsertBatch = ['SetEdit'];
    protected $beforeUpdateBatch = ['SetEdit'];

    protected $afterInsert = ['SetView'];
    protected $afterUpdate = ['SetView'];
    protected $afterInsertBatch = ['SetView'];
    protected $afterUpdateBatch = ['SetView'];

    public function __construct()
    {
        $this->table = HutBookingsModel::ViewTable;
        parent::__construct();
    }

    public function findByMember($id, $pageSize)
    {
        $today = (new \DateTime("today"))->format('Y-m-d');
        return $this->where(["member_id" => $id, "start_date >=" => $today])
                    ->orderBy("start_date", "asc")
                    ->paginate($pageSize);
    }

    public function findById($id, $onlyForUser=null)
    {
        $params = [ 'id' => $id ];
        if ($onlyForUser) {
            $params['member_id'] = $onlyForUser;
        }
        return $this->where($params)->findAll();
    }

    public function tryCreate(HutBooking $booking)
    {
        if (!$this->isAdmin()) {
            // Non admins may only make bookings for themselves. We check that the member_id
            // is the same as the logged in user and that the booking type is "Member"
            if ($booking->member_id != '' && session()->userID != $booking->member_id) {
                return ["result" => "You don't have permission to do that"];
            } else if ($booking->type != "Member") {
                return ["result" => "Non-admins may only make member bookings"];
            }
        }
        if ($booking->type == "Member" && $booking->member_id == '') {
            $booking->member_id = session()->userID;
        }
        if ($invalid = $this->ValidateBooking($booking)) {
            return $invalid;
        }
        // Check that there are sufficient bunks
        $dt = \DateTime::createFromFormat("Y-m-d", $booking->start_date);
        $available = true;
        for($i=0; $i<$booking->nights; $i++) {
            $date = $dt->format('Y-m-d');
            if($this->bunksAvailableOnDate($date) < $booking->bunks) {
                $available = false;
                break;
            }
            $dt->modify('+1 day');
        }
        if(!$available) {
            return ["result" => "Insufficient bunks available on one or more nights"];
        }

        $door_code = model('HutDoorCodesModel')->atDate($booking->start_date)->code;
        $booking->door_code = $door_code;
        $success = $this->insert($booking);
        if ($success) {
            $this->table = HutBookingsModel::ViewTable;
            $booking = $this->find($this->getInsertID());
            $this->SendBookingConfirmation($booking, true);
            $this->NotifyBookingCreated($booking);
            return ["result" => "OK", "booking" => $booking];
        }
        return ["result" => "Unknown failure"];
    }

    public function tryUpdate(HutBooking $updateRequest)
    {
        $existingBooking = $this->find($updateRequest->id);
        if (!$existingBooking) {
            return ["result" => "No booking with id=$id"];
        }
        if (!$this->isAdmin() && session()->userID != $existingBooking->member_id) {
            return ["result" => "You don't have permission to do that"];
        }
        if ($existingBooking->status == "Cancelled") {
            return ["result" => "Cannot modify a cancelled booking"];
        }
        // Standard checks
        if ($invalid = $this->ValidateBooking($updateRequest)) {
            return $invalid;
        }
        // Check that there are sufficient bunks to accomodate any change in requirements
        if ($updateRequest->status != "Cancelled" &&
            (isset($updateRequest->bunks) && $updateRequest->bunks != $existingBooking->bunks) ||
            (isset($updateRequest->start_date) && $updateRequest->start_date != $existingBooking->start_date) ||
            (isset($updateRequest->nights) && $updateRequest->nights != $existingBooking->nights)) {
            $dt = \DateTime::createFromFormat("Y-m-d", $updateRequest->start_date);
            $oldFirstNight = \DateTimeImmutable::createFromFormat("Y-m-d", $existingBooking->start_date);
            $oldLastNight = $oldFirstNight->modify('+' . ($existingBooking->nights-1) . ' days');
            $available = true;
            for($i=0; $i<$updateRequest->nights; $i++) {
                $date = $dt->format('Y-m-d');
                // If this day of the new booking is outwith the existing booking, then we need to check for the full
                // number of bunks, otherwise we only need to check for the additional bunks required (if any)
                $additionalBunksRequired = ($date < $oldFirstNight->format('Y-m-d') || 
                                            $date > $oldLastNight->format('Y-m-d') ) ? 
                                            $updateRequest->bunks : max($updateRequest->bunks - $existingBooking->bunks, 0);
                if($this->bunksAvailableOnDate($date) < $additionalBunksRequired) {
                    $available = false;
                    break;
                }
                $dt->modify('+1 day');
            }
            if(!$available) {
                return ["result" => "Insufficient bunks available on one or more nights"];
            }
        }
        // Copy the updated fields into the booking
        $update = $existingBooking->toArray();
        foreach($update as $key => $value) {
            if (isset($updateRequest->$key)) {
                $update[$key] = $updateRequest->$key;
            }
        }
        $updatedBooking = new \App\Models\HutBooking($update);
        // Update the door code
        $door_code = model('HutDoorCodesModel')->atDate($updatedBooking->start_date)->code;
        $updatedBooking->door_code = $door_code;
        
        if ($this->save($updatedBooking)) {
            if ($updatedBooking->status == "Cancelled") {
                $this->NotifyBookingCancelled($updatedBooking);
            } else {
                $this->NotifyBookingModified($existingBooking, $updatedBooking);
                $this->SendBookingConfirmation($updatedBooking, false);
            }
            $updatedBookingdBooking = $this->find($updatedBooking->id);
            return ["result" => "OK", "booking" => $updatedBookingdBooking];
        }
    }

    /*
    public function tryDelete($id)
    {
        $booking = $this->find($booking->id);
        if (!$booking) {
            return ["result" => "No booking with id=$id"];
        }
        if (!$this->isAdmin() && session()->userID != $booking->member_id) {
            return ["result" => "You don't have permission to do that"];
        }
        if ($booking->status == "Cancelled") {
            return ["result" => "Booking already cancelled"];
        }
        
        if ($this->delete($booking)) {
            $this->NotifyBookingCancelled($booking);
            return ["result" => "OK", "booking" => $booking];
        }
        return ["result" => "Unexpected error", "booking" => $booking];
    }
    */

    // date is like 2023-08-11
    public function bunksAvailableOnDate($date)
    {
        $q = $this->db->query("SELECT (".self::MaxBunks." - COALESCE(SUM(bunks),0)) as availability FROM `bookings` WHERE
                               start_date <= '$date' AND DATE_ADD(start_date, INTERVAL nights DAY) > '$date' AND status != 'Cancelled'");
        return $q->getRow()->availability;
    }

    protected function SetEdit($data)
    {
        $this->table = HutBookingsModel::EditTable;
        $this->builder = null;
        return $data;
    }

    protected function SetView($data)
    {
        $this->table = HutBookingsModel::ViewTable;
        $this->builder = null;
        return $data;
    }

    private function ValidateBooking($booking)
    {
        // Basic validation
        if ( $booking->start_date < date("Y-m-d") ) {
            return ["result" => "Cannot book for a date in the past ($booking->start_date)"];
        }
        if ( $booking->nights < 1 ) {
            return ["result" => "Invalid number of nights"];
        }
        if ( $booking->bunks < 1 || $booking->bunks > self::MaxBunks ) {
            return ["result" => "Invalid number of bunks"];
        }
        return null;
    }

    private function isAdmin()
    {
        return (session()->userID != 0) && (count(session()->roles) > 0);
    }

    private function SendBookingConfirmation($booking, $isNewBooking)
    {
        // Send an email to the member confirming the booking
        if ($isNewBooking) {
            $bookingDetails = "Your booking for $booking->bunks bunks at the hut from $booking->start_date for $booking->nights nights has been confirmed.";
        } else {
            $bookingDetails = "Your booking at the CTC has been modified to $booking->bunks bunks from\n$booking->start_date for $booking->nights nights.";
        }
        if ($booking->type == "Member") {
            $update = "If you need to modify or cancel your booking you can do so on the CTC website.";
        } else {
            $update = "If you need to modify or cancel your booking please email hutbooking@ctc.org.nz.";
        }

        $name = explode(" ", $booking->name)[0];
        $text = <<<EOT
Dear $name,

$bookingDetails

The hut code during your stay will be $booking->door_code.

Hut fees are $15 member, $15 member's immediate family (life partner,
children grandchildren), $25 non-member, $10 children 10-17 inclusive,
free children under 10. Please pay these fees into the CTC bank account:
    Account: Kiwibank 38-9017-0279838-00
    Account Name: Christchurch Tramping

Please include your name, the words "hut fees", and the dates of your stay in the particulars/code/reference fields.

$update

In case of any problems, please contact Don on 0210 259 3229 or Rex 022 197 8101.

We hope you enjoy your stay!
EOT;

        $to = $booking->email;
        $from = "hutbooking@ctc.org.nz";
        $fromName = "Christchurch Tramping Club";
        $subject = "CTC Hut Booking Confirmation";

        helper('utilities');
        sendEmail($from, $fromName, $to, $subject, $text);
    }

    private function SendBookingCancellation($booking)
    {
        $text = <<<EOT
Dear $booking->name,

Your CTC hut booking for $booking->start_date for $booking->nights nights has been cancelled.

If this is unexpected, please contact hutbooking@ctc.org.nz, or for urgent matters Rex on 022 197 8101.

We hope to see you again soon!
EOT;

        $to = $booking->email;
        $from = "hutbooking@ctc.org.nz";
        $fromName = "Christchurch Tramping Club";
        $subject = "CTC Hut Booking Confirmation";

        helper('utilities');
        sendEmail($from, $fromName, $to, $subject, $text);
    }

    private function NotifyBookingCreated($booking)
    {
        // Send an email to the hut booking team
        $text = <<<EOT
A new CTC hut booking has been created.

Name: $booking->name
Email: $booking->email
Phone: $booking->phone
Start date: $booking->start_date
Nights: $booking->nights
Bunks: $booking->bunks
EOT;
        $to = "hutbooking@ctc.org.nz";
        $from = "hutbooking@ctc.org.nz";
        $fromName = "CTC Hut Booking";
        $subject = "New CTC Hut Booking";

        helper('utilities');
        sendEmail($from, $fromName, $to, $subject, $text);
    }

    private function NotifyBookingModified($oldBooking, $newBooking)
    {
        $fields = ["start_date", "nights", "bunks"];
        $details = "";
        foreach($fields as $field) {
            if ($oldBooking->$field != $newBooking->$field) {
                $details .= "$field: ".$oldBooking->$field." -> ".$newBooking->$field."\n";
            } else {
                $details .= "$field: ".$oldBooking->$field."\n";
            }
        }
        // Send an email to the hut booking team
        $text = <<<EOT
A CTC hut booking has been modified.

Name: $newBooking->name
Email: $newBooking->email
Phone: $newBooking->phone
$details
EOT;

        $to = "hutbooking@ctc.org.nz";
        $from = "hutbooking@ctc.org.nz";
        $fromName = "CTC Hut Booking";
        $subject = "CTC Hut Booking Modified";

        helper('utilities');
        sendEmail($from, $fromName, $to, $subject, $text);
    }

    private function NotifyBookingCancelled($booking)
    {
        // Send an email to the hut booking team
        $text = <<<EOT
The following CTC hut booking has been cancelled.

Name: $booking->name
Email: $booking->email
Phone: $booking->phone
Start date: $booking->start_date
Nights: $booking->nights
Bunks: $booking->bunks
EOT;
        $to = "hutbooking@ctc.org.nz";
        $from = "hutbooking@ctc.org.nz";
        $fromName = "CTC Hut Booking";
        $subject = "CTC Hut Booking Cancelled";

        helper('utilities');
        sendEmail($from, $fromName, $to, $subject, $text);
    }

}
