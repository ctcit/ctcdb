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
            if (session()->userID != $booking->member_id) {
                return ["result" => "You don't have permission to do that"];
            } else if ($data->type != "Member") {
                return ["result" => "Non-admins may only make member bookings"];
            }
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
        $this->table = HutBookingsModel::ViewTable;
        if ($success) {
            $this->SendBookingConfirmation($booking, true);
            return ["result" => "OK", "booking" => $this->find($this->getInsertID())];
        }
        return ["result" => "Unknown failure"];
    }

    public function tryUpdate(HutBooking $booking)
    {
        $existingBooking = $this->find($booking->id);
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
        if ($invalid = $this->ValidateBooking($booking)) {
            return $invalid;
        }
        // Check that there are sufficient bunks to accomodate any change in requirements
        if ((isset($booking->bunks) && $booking->bunks != $existingBooking->bunks) ||
            (isset($booking->start_date) && $booking->start_date != $existingBooking->start_date) ||
            (isset($booking->nights) && $booking->nights != $existingBooking->nights)) {
            $dt = \DateTime::createFromFormat("Y-m-d", $booking->start_date);
            $oldFirstNight = \DateTimeImmutable::createFromFormat("Y-m-d", $existingBooking->start_date);
            $oldLastNight = $oldFirstNight->modify('+' . ($existingBooking->nights-1) . ' days');
            $available = true;
            for($i=0; $i<$booking->nights; $i++) {
                $date = $dt->format('Y-m-d');
                // If this day of the new booking is outwith the existing booking, then we need to check for the full
                // number of bunks, otherwise we only need to check for the additional bunks required (if any)
                $additionalBunksRequired = ($date < $oldFirstNight->format('Y-m-d') || 
                                            $date > $oldLastNight->format('Y-m-d') ) ? 
                                            $booking->bunks : max($booking->bunks - $existingBooking->bunks, 0);
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
            if (isset($booking->$key)) {
                $update[$key] = $booking->$key;
            }
        }
        $booking = new \App\Models\HutBooking($update);
        // Update the door code
        $door_code = model('HutDoorCodesModel')->atDate($booking->start_date)->code;
        $booking->door_code = $door_code;
        
        if ($this->save($booking)) {
            $this->SendBookingConfirmation($booking, false);
            $updatedBooking = $this->find($booking->id);
            return ["result" => "OK", "booking" => $updatedBooking];
        }
    }

    // date is like 2023-08-11
    public function bunksAvailableOnDate($date)
    {
        $q = $this->db->query("SELECT (".self::MaxBunks." - COALESCE(SUM(bunks),0)) as availability FROM `bookings` WHERE
                               start_date <= '$date' AND DATE_ADD(start_date, INTERVAL nights DAY) > '$date'");
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
        $text = <<<EOT
Dear $booking->name,

Your booking for $booking->bunks bunks at the hut from
$booking->start_date for $booking->nights nights has been confirmed.

The hut code during your stay will be $booking->door_code.

Hut fees are $15 member, $15 member's immediate family (life partner,
children grandchildren), $25 non-member, $10 children 10-17 inclusive,
free children under 10. Please pay these fees into the CTC bank account:
    Account: Kiwibank 38-9017-0279838-00
    Account Name: Christchurch Tramping
Please include your name and the words "hut fees" in the particulars/code/reference fields.
Also date of stay (first night if multiple) is useful if you have a 3rd reference field available.

If you need to modify or cancel your booking you can do so on the CTC website.

In case of any problems, please contact Don on 0210 259 3229 or Rex 022 197 8101.

We hope you enjoy your stay!
EOT;

        $headers = "MIME-Version: 1.0\r\n".
                "Content-type: text/html;charset=UTF-8\r\n".
                "From: <noreply@ctc.org.nz>\r\n";

        //$to = $booking->email;
        $to = "nickedwrds@gmail.com";

        $subject = "CTC Hut Booking Confirmation";

        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            try {
                mail($to, $subject, $text, $headers);
            } catch (\Exception $e) {
                log_message('error', 'Failed to send booking confirmation email: ' . $e->getMessage());
            }
        }
    }
}
