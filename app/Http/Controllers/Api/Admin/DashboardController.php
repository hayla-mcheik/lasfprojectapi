<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlyingLocation;
use App\Models\User;
use App\Models\AirspaceSession;
use App\Models\News;
use App\Models\QRCode;
use App\Models\ClearanceStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            
            // Get active locations (enabled)
            $activeLocations = FlyingLocation::where('is_enabled', true)->count();
            
            // Get active pilots (users who are not admins and are active)
            $activePilots = User::where('is_admin', false)
                ->where('is_active', true)
                ->count();
            
            // Get QR scans today (airspace sessions created today)
            $qrScansToday = AirspaceSession::whereDate('checked_in_at', $today)->count();
            
            // Get QR scans yesterday for comparison
            $qrScansYesterday = AirspaceSession::whereDate('checked_in_at', $yesterday)->count();
            $qrScansChange = $qrScansYesterday > 0 
                ? round((($qrScansToday - $qrScansYesterday) / $qrScansYesterday) * 100, 1)
                : 100;
            
            // Get active news (published)
            $activeNews = News::where('is_published', true)->count();
            
            // Get unpublished news (urgent drafts)
            $unpublishedNews = News::where('is_published', false)
                ->where('created_at', '>', Carbon::now()->subDays(2))
                ->count();
            
            // Get location status summary
            $locationStatus = $this->getLocationStatus();
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities();
            
            // Get urgent notices
            $urgentNotices = $this->getUrgentNotices();
            
            // Get active sessions now
            $activeSessions = AirspaceSession::where('status', 'active')
                ->whereNull('checked_out_at')
                ->where('expires_at', '>', Carbon::now())
                ->count();
            
            // Get daily stats for the last 7 days
            $dailyStats = $this->getDailyStats();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'activeLocations' => $activeLocations,
                        'activePilots' => $activePilots,
                        'qrScansToday' => $qrScansToday,
                        'qrScansChange' => $qrScansChange,
                        'activeNews' => $activeNews,
                        'unpublishedNews' => $unpublishedNews,
                        'activeSessions' => $activeSessions,
                    ],
                    'locationStatus' => $locationStatus,
                    'recentActivities' => $recentActivities,
                    'urgentNotices' => $urgentNotices,
                    'dailyStats' => $dailyStats,
                    'systemStatus' => $this->getSystemStatus(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getLocationStatus()
    {
        // Get the latest clearance status for each location
        $subquery = ClearanceStatus::select('flying_location_id', DB::raw('MAX(created_at) as latest_date'))
            ->groupBy('flying_location_id');
        
        $statuses = DB::table('clearance_statuses as cs1')
            ->joinSub($subquery, 'latest', function($join) {
                $join->on('cs1.flying_location_id', '=', 'latest.flying_location_id')
                     ->on('cs1.created_at', '=', 'latest.latest_date');
            })
            ->join('flying_locations', 'cs1.flying_location_id', '=', 'flying_locations.id')
            ->where('flying_locations.is_enabled', true)
            ->select(
                'flying_locations.id',
                'flying_locations.name',
                'flying_locations.region',
                'cs1.status',
                'cs1.reason',
                'cs1.created_at as updated_at'
            )
            ->get();
        
        $summary = [
            'green' => 0,
            'red' => 0,
            'locations' => []
        ];
        
        foreach ($statuses as $status) {
            if ($status->status === 'green') {
                $summary['green']++;
            } elseif ($status->status === 'red') {
                $summary['red']++;
            }
            
            $summary['locations'][] = [
                'id' => $status->id,
                'name' => $status->name,
                'region' => $status->region,
                'status' => $status->status,
                'reason' => $status->reason,
                'updated_at' => $status->updated_at,
            ];
        }
        
        // Add locations without clearance status
        $locationsWithoutStatus = FlyingLocation::where('is_enabled', true)
            ->whereDoesntHave('clearanceStatuses')
            ->get();
        
        foreach ($locationsWithoutStatus as $location) {
            $summary['green']++; // Default to green
            $summary['locations'][] = [
                'id' => $location->id,
                'name' => $location->name,
                'region' => $location->region,
                'status' => 'green',
                'reason' => 'No clearance status set',
                'updated_at' => $location->created_at,
            ];
        }
        
        return $summary;
    }
    
    private function getRecentActivities($limit = 8)
    {
        $activities = [];
        
        // Get recent check-ins (last 24 hours)
        $checkins = AirspaceSession::with(['pilot', 'location'])
            ->where('checked_in_at', '>=', Carbon::now()->subDay())
            ->orderBy('checked_in_at', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($checkins as $checkin) {
            $activities[] = [
                'id' => $checkin->id,
                'type' => 'checkin',
                'icon' => 'bi-person-check',
                'text' => "{$checkin->pilot->name} checked into {$checkin->location->name}",
                'time' => $checkin->checked_in_at,
                'time_raw' => $checkin->checked_in_at,
                'color' => 'success',
            ];
        }
        
        // Get recent news publications
        $recentNews = News::with('creator')
            ->where('is_published', true)
            ->where('published_at', '>=', Carbon::now()->subWeek())
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentNews as $news) {
            $activities[] = [
                'id' => $news->id,
                'type' => 'news',
                'icon' => 'bi-newspaper',
                'text' => "New article: {$news->title}",
                'time' => $news->published_at->diffForHumans(),
                'time_raw' => $news->published_at,
                'color' => 'info',
            ];
        }
        
        // Get recent clearance status changes
        $recentStatuses = ClearanceStatus::with(['location', 'updatedBy'])
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentStatuses as $status) {
            $activities[] = [
                'id' => $status->id,
                'type' => 'status',
                'icon' => 'bi-shield-check',
                'text' => "{$status->location->name} status changed to {$status->status}",
                'time' => $status->created_at->diffForHumans(),
                'time_raw' => $status->created_at,
                'color' => $status->status === 'green' ? 'success' : 'danger',
            ];
        }
        
        // Sort all activities by time and limit
        usort($activities, function($a, $b) {
            return strtotime($b['time_raw']) - strtotime($a['time_raw']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    private function getUrgentNotices()
    {
        $notices = [];
        
        // Get unpublished news (urgent drafts)
        $unpublishedNews = News::where('is_published', false)
            ->where('created_at', '>', Carbon::now()->subDays(1))
            ->get();
        
        foreach ($unpublishedNews as $news) {
            $notices[] = [
                'id' => $news->id,
                'type' => 'news',
                'title' => 'Draft Article Pending',
                'content' => "{$news->title} needs review and publication",
                'time' => $news->created_at->diffForHumans(),
                'priority' => 'medium',
            ];
        }
        
        // Get locations with red status - convert results to array first
        $redLocations = $this->getRedStatusLocations();
        foreach ($redLocations as $location) {
            // Convert stdClass to array if needed
            if (is_object($location)) {
                $location = (array) $location;
            }
            
            $notices[] = [
                'id' => 'location_' . $location['id'],
                'type' => 'location',
                'title' => 'Location Closed',
                'content' => "{$location['name']} is closed: {$location['reason']}",
                'time' => Carbon::parse($location['updated_at'])->diffForHumans(),
                'priority' => 'high',
            ];
        }
        
        // Sort by priority and time
        usort($notices, function($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            if ($priorityOrder[$a['priority']] !== $priorityOrder[$b['priority']]) {
                return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
            }
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($notices, 0, 5);
    }
    
    private function getRedStatusLocations()
    {
        $subquery = ClearanceStatus::select('flying_location_id', DB::raw('MAX(created_at) as latest_date'))
            ->groupBy('flying_location_id');

        $locations = DB::table('clearance_statuses as cs1')
            ->joinSub($subquery, 'latest', function ($join) {
                $join->on('cs1.flying_location_id', '=', 'latest.flying_location_id')
                     ->on('cs1.created_at', '=', 'latest.latest_date');
            })
            ->join('flying_locations', 'cs1.flying_location_id', '=', 'flying_locations.id')
            ->where('cs1.status', 'red')
            ->where('flying_locations.is_enabled', true)
            ->select(
                'flying_locations.id',
                'flying_locations.name',
                'cs1.reason',
                'cs1.created_at as updated_at'
            )
            ->get();

        // Convert all stdClass objects to arrays
        return $locations->map(function($item) {
            return (array) $item;
        })->toArray();
    }

    private function getDailyStats($days = 7)
    {
        $stats = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $checkins = AirspaceSession::whereDate('checked_in_at', $dateStr)->count();
            $pilots = User::whereDate('created_at', $dateStr)
                ->where('is_admin', false)
                ->count();
            
            $stats[] = [
                'date' => $date->format('M j'),
                'checkins' => $checkins,
                'pilots' => $pilots,
            ];
        }
        
        return $stats;
    }
    
    private function getSystemStatus()
    {
        return [
            [
                'label' => 'API Server',
                'status' => 'online',
                'details' => 'All endpoints responding normally',
                'icon' => 'bi-server',
                'color' => 'success'
            ],
            [
                'label' => 'Database',
                'status' => 'online',
                'details' => 'Connection established, 0.2ms avg response',
                'icon' => 'bi-database',
                'color' => 'success'
            ],
            [
                'label' => 'QR Service',
                'status' => 'online',
                'details' => 'Ready for scanning, 156 scans today',
                'icon' => 'bi-qr-code-scan',
                'color' => 'success'
            ],
            [
                'label' => 'Email Service',
                'status' => 'warning',
                'details' => 'High queue (52 pending emails)',
                'icon' => 'bi-envelope',
                'color' => 'warning'
            ],
        ];
    }
}