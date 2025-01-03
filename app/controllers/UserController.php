<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Database;
use PDO;
use PDOException;
use DateTime;

class UserController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function index()
    {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Fetch all pending register members
            $sql = "SELECT *
                    FROM pendingregistermember 
                    ORDER BY id DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            $pendingregistermembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pass the data to the view
            $this->view('users/index', compact('pendingregistermembers'));
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error fetching pending members: " . $e->getMessage();
            $this->view('users/index', ['pendingregistermembers' => []]);
        }
    }

    public function create()
    {
        $this->view('users/create');
    }

    public function store()
    {
        try {
            if ($this->user->create($_POST)) {
                $_SESSION['success'] = "Pendaftaran anda telah berjaya dihantar dan sedang dalam proses pengesahan.";
                header('Location: /');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Ralat semasa pendaftaran: " . $e->getMessage();
            header('Location: /users/create');
            exit;
        }
    }

    public function edit($id)
    {
        // Fetch the user data using the ID
        $user = $this->user->find($id);

        // Pass the user data to the 'users/edit' view
        $this->view('users/edit', compact('user'));
    }

    public function update($id)
    {
        $this->user->update($id, $_POST);
        header('Location: /');
    }

    public function delete($id)
    {
        $this->user->delete($id);
        header('Location: /');
    }

    public function showSavingsDashboard()
    {
        $this->checkAuth();
        try {
            $memberId = $_SESSION['admin_id'];

            // Get total savings
            $totalSavings = $this->user->getTotalSavings($memberId);

            // Get savings goals
            $savingsGoals = $this->user->getSavingsGoals($memberId);

            // Get recurring payment settings
            $recurringPayment = $this->user->getRecurringPayment($memberId);

            // Get recent transactions
            $recentTransactions = $this->user->getRecentTransactions($memberId);

            $this->view('admin/savings/dashboard', [
                'totalSavings' => $totalSavings,
                'savingsGoals' => $savingsGoals,
                'recurringPayment' => $recurringPayment,
                'recentTransactions' => $recentTransactions
            ]);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->view('admin/savings/dashboard', [
                'totalSavings' => 0,
                'savingsGoals' => [],
                'recurringPayment' => null,
                'recentTransactions' => []
            ]);
        }
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /auth/login');
            exit();
        }
    }
}
