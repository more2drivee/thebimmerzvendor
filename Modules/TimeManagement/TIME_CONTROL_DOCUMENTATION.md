# Time Control Module Documentation

## Overview
The Time Control module provides real-time timer management for job sheets in the repair management system. It allows technicians to track work time per job sheet and provides managers with live monitoring capabilities.

## Core Concepts

### 1. Timer States
- **Active**: Timer is currently running and counting time
- **Paused**: Timer is stopped but can be resumed (paused time not counted)
- **Completed**: Timer is finished and can no longer be modified

### 2. Timer Architecture
- **Per-User Timers**: Each technician has individual timers for each job sheet
- **Bulk Operations**: Entire job sheets can be controlled simultaneously
- **Real-Time Updates**: Live elapsed time display and automatic refresh
- **Pause/Resume Logic**: Paused time is tracked separately and subtracted from total

## Web Interface Logic

### TimeControlController.php

#### Individual Timer Actions

```php
// Start a new timer for a specific technician on a job sheet
public function startTimer(Request $request)
{
    // Required: job_sheet_id, user_id
    // Creates timer_tracking record with status 'active'
}

// Pause an active timer
public function pauseTimer(Request $request)
{
    // Updates timer status to 'paused' and records paused_at timestamp
}

// Resume a paused timer
public function resumeTimer(Request $request)
{
    // Calculates pause duration and adds to total_paused_duration
    // Updates status back to 'active'
}

// Complete a timer
public function completeTimer(Request $request)
{
    // Sets completed_at timestamp and status to 'completed'
}
```

#### Bulk Operations

```php
// Start timers for all assigned technicians on a job sheet
public function playAll(Request $request)
{
    // Gets service_staff array from job sheet
    // Creates active timers for technicians without existing active timers
    // Resumes paused timers for technicians with existing paused timers
}

// Pause all active timers on a job sheet
public function pauseAll(Request $request)
{
    // Updates all active timers to 'paused' status
}

// Complete all active/paused timers on a job sheet
public function completeAll(Request $request)
{
    // Completes all timers, accounting for final pause duration if needed
}
```

#### Data Retrieval

```php
// Get live timers with pagination and statistics
public function list(Request $request, TimeMetricsService $metrics)
{
    $timers = $metrics->getLiveTimers($business_id, $filters);
    // Returns paginated data with stats (active_count, total_seconds, unique_techs)
}
```

### View Layer (index.blade.php)

#### Real-Time Features

```javascript
// Auto-refresh every 30 seconds
setInterval(refresh, 30000);

// Local ticking for active timers every 1 second
setInterval(function(){
    var activeTimers = document.querySelectorAll('.elapsed[data-active="1"]');
    // Increment elapsed time for each active timer locally
}, 1000);
```

#### UI Structure

```
┌─────────────────────────────────────────────────────────────┐
│ KPI Cards: Active Timers | Total Time | Technicians Active │
├─────────────────────────────────────────────────────────────┤
│ Job Sheet Cards:                                            │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Status Badge | JS-Number | Workshop Name               │ │
│ │ Device Details | Technician List                       │ │
│ │ Elapsed Time | Play/Pause/Stop (Bulk)                 │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Individual Technician Timers:                          │ │
│ │ ┌─────────────────────────────────────────────────────┐ │ │
│ │ │ Technician Name | Individual Time | Controls       │ │ │
│ │ └─────────────────────────────────────────────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

#### Key UI Behaviors

1. **Bulk Controls**: Apply to entire job sheet
   - Play All: Start/resume all assigned technicians
   - Pause All: Pause all active timers
   - Stop All: Complete all active/paused timers

2. **Individual Controls**: Apply to specific technician
   - Start: Create new timer for technician
   - Pause: Stop individual timer
   - Resume: Continue paused timer
   - Stop: Complete individual timer

3. **Real-Time Updates**:
   - Elapsed time ticks every second for active timers
   - Full refresh every 30 seconds to sync with server
   - Pagination controls for large datasets

## Database Schema

### timer_tracking Table

```sql
CREATE TABLE timer_tracking (
    id INT PRIMARY KEY,
    business_id INT,
    job_sheet_id INT,
    user_id INT,
    status ENUM('active', 'paused', 'completed'),
    started_at TIMESTAMP,
    paused_at TIMESTAMP NULL,
    resumed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    total_paused_duration INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Key Fields Explanation

- `total_paused_duration`: Cumulative seconds spent in paused state
- `paused_at`: Timestamp when current pause began (NULL if not paused)
- `resumed_at`: Timestamp when last resumed from pause

### Time Calculation Logic

```php
// Calculate elapsed time for active timer
$elapsed = now() - started_at - total_paused_duration;

// Calculate elapsed time for paused timer
$elapsed = paused_at - started_at - total_paused_duration;

// Calculate elapsed time for completed timer
$elapsed = completed_at - started_at - total_paused_duration;
```

## API Design Principles

### Required API Methods

Based on web controller functionality, the API should provide:

#### Individual Timer Management
- `POST /api/timers/start` - Start timer (job_sheet_id, user_id)
- `POST /api/timers/{id}/pause` - Pause timer
- `POST /api/timers/{id}/resume` - Resume timer
- `POST /api/timers/{id}/complete` - Complete timer

#### Bulk Timer Management
- `POST /api/timers/play-all` - Start all timers for job sheet
- `POST /api/timers/pause-all` - Pause all timers for job sheet
- `POST /api/timers/complete-all` - Complete all timers for job sheet

#### Data Retrieval
- `GET /api/timers` - Get live timers with pagination
- `GET /api/timers/history` - Get completed timers history

### API Response Consistency

All API responses should follow the web interface data structure:

```json
{
  "timers": [
    {
      "id": 1,
      "job_sheet_id": 123,
      "job_sheet_no": "JS-001",
      "workshop_name": "Main Workshop",
      "status_name": "In Progress",
      "status_color": "#28a745",
      "elapsed_seconds": 3600,
      "service_staff": [1, 2, 3],
      "technicians": ["John Doe", "Jane Smith"],
      "device": {
        "name": "Toyota",
        "model": "Camry",
        "plate_number": "ABC-123"
      },
      "workers": [
        {
          "user_id": 1,
          "user_name": "John Doe",
          "timer_id": 456,
          "timer_status": "active",
          "elapsed_seconds": 1800
        }
      ]
    }
  ],
  "stats": {
    "active_count": 5,
    "total_seconds": 18000,
    "unique_techs": 8
  },
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 25,
    "has_more": true
  }
}
```

## Performance Considerations

### Database Optimization
- Use indexes on `job_sheet_id`, `user_id`, `status`, `completed_at`
- Avoid N+1 queries by eager loading related data
- Use database-level time calculations where possible

### Caching Strategy
- Cache technician lists and job sheet details
- Cache aggregated statistics for dashboard
- Invalidate cache on timer state changes

### Real-Time Updates
- WebSocket/SSE for instant timer updates
- Fallback to polling for older browsers
- Optimize polling frequency based on active timers

## Error Handling

### Validation Rules
- Job sheet must exist and belong to business
- User must be assigned to job sheet (in service_staff array)
- Cannot start timer if user already has active timer
- Cannot pause already paused timer

### Business Logic Validation
- Only service staff can have timers
- Job sheet must be in appropriate status
- Prevent concurrent timer operations

## Testing Strategy

### Unit Tests
- Timer state transitions
- Time calculation accuracy
- Bulk operation logic
- Validation rules

### Integration Tests
- API endpoint functionality
- Real-time UI updates
- Database consistency
- Performance under load

### E2E Tests
- Complete timer workflows
- Bulk operations
- Real-time synchronization
- Error scenarios

## Future Enhancements

### Advanced Features
- Timer categories/tags
- Time tracking reports with charts
- Automatic timer start/stop based on location
- Integration with project management tools

### Performance Improvements
- Redis caching for real-time data
- Database partitioning for large datasets
- Optimized queries with CTEs/subqueries
