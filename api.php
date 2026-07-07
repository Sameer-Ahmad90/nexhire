<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function to_datetime_local(?string $dt): ?string
{
    if ($dt === null || $dt === '') return null;
    // MySQL DATETIME: "YYYY-MM-DD HH:MM:SS" -> HTML datetime-local: "YYYY-MM-DDTHH:MM"
    $dt = str_replace(' ', 'T', $dt);
    return preg_replace('/:\d{2}$/', '', $dt);
}

function from_datetime_local(?string $dt): ?string
{
    if ($dt === null || $dt === '') return null;
    // "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:SS"
    $dt = str_replace('T', ' ', $dt);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dt)) $dt .= ':00';
    return $dt;
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'init';
        if ($action !== 'init') json_response(['ok' => false, 'error' => 'Unknown action'], 400);

        $pdo = db();

        $companies = $pdo->query('SELECT Company_Id AS id, Name AS name, Industry AS industry, Location AS location, Website AS website, Founded_Year AS year FROM Company ORDER BY Company_Id')->fetchAll();
        $candidates = $pdo->query('SELECT Candidate_ID AS id, Name AS name, Email AS email, Phone AS phone, Address AS address, DOB AS dob FROM Candidate ORDER BY Candidate_ID')->fetchAll();
        $jobs = $pdo->query("SELECT Job_Id AS id, Company_Id AS companyId, Title AS title, Description AS `desc`, Salary_Range AS salary, Employment_Type AS type, Posted_Date AS posted, Deadline AS deadline FROM Job_Posting ORDER BY Job_Id")->fetchAll();
        $applications = $pdo->query("SELECT App_ID AS id, Candidate_ID AS candidateId, Job_ID AS jobId, Applied_Date AS date, Status AS status, Cover_Letter AS cover FROM Application ORDER BY App_ID")->fetchAll();
        $interviewers = $pdo->query("SELECT Interviewer_ID AS id, Company_ID AS companyId, Name AS name, Email AS email, Department AS dept FROM Interviewer ORDER BY Interviewer_ID")->fetchAll();
        $interviews = $pdo->query("SELECT Interview_ID AS id, App_ID AS appId, Interviewer_ID AS ivrId, Interview_Type AS type, Location_Link AS loc, Schedule_Date AS date FROM Interview ORDER BY Interview_ID")->fetchAll();
        foreach ($interviews as &$i) { $i['date'] = to_datetime_local($i['date']); }
        unset($i);
        $offers = $pdo->query("SELECT Offer_ID AS id, App_ID AS appId, Offered_Salary AS salary, Offer_Date AS date, Expiry_Date AS expiry FROM Offer ORDER BY Offer_ID")->fetchAll();
        $decisions = $pdo->query("SELECT Decision_ID AS id, Offer_ID AS offerId, Decision AS decision, Decision_Date AS date, Reason AS reason FROM Hiring_Decision ORDER BY Decision_ID")->fetchAll();

        json_response([
            'ok' => true,
            'data' => [
                'companies' => $companies,
                'candidates' => $candidates,
                'jobs' => $jobs,
                'applications' => $applications,
                'interviewers' => $interviewers,
                'interviews' => $interviews,
                'offers' => $offers,
                'decisions' => $decisions,
            ],
        ]);
    }

    // POST JSON API
    if ($method !== 'POST') json_response(['ok' => false, 'error' => 'Method not allowed'], 405);

    $body = read_json_body();
    $resource = (string)($body['resource'] ?? '');
    $action = (string)($body['action'] ?? '');
    $data = is_array($body['data'] ?? null) ? $body['data'] : [];

    $pdo = db();

    // helper to validate int id
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    switch ($resource) {
        case 'companies':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Company (Name, Industry, Location, Website, Founded_Year) VALUES (?,?,?,?,?)');
                $stmt->execute([
                    trim((string)($data['name'] ?? '')),
                    ($data['industry'] ?? '') !== '' ? trim((string)$data['industry']) : null,
                    ($data['location'] ?? '') !== '' ? trim((string)$data['location']) : null,
                    ($data['website'] ?? '') !== '' ? trim((string)$data['website']) : null,
                    ($data['year'] ?? null) !== null && $data['year'] !== '' ? (int)$data['year'] : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Company SET Name=?, Industry=?, Location=?, Website=?, Founded_Year=? WHERE Company_Id=?');
                $stmt->execute([
                    trim((string)($data['name'] ?? '')),
                    ($data['industry'] ?? '') !== '' ? trim((string)$data['industry']) : null,
                    ($data['location'] ?? '') !== '' ? trim((string)$data['location']) : null,
                    ($data['website'] ?? '') !== '' ? trim((string)$data['website']) : null,
                    ($data['year'] ?? null) !== null && $data['year'] !== '' ? (int)$data['year'] : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Company WHERE Company_Id=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'candidates':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Candidate (Name, Email, Phone, Address, DOB) VALUES (?,?,?,?,?)');
                $stmt->execute([
                    trim((string)($data['name'] ?? '')),
                    trim((string)($data['email'] ?? '')),
                    ($data['phone'] ?? '') !== '' ? trim((string)$data['phone']) : null,
                    ($data['address'] ?? '') !== '' ? trim((string)$data['address']) : null,
                    ($data['dob'] ?? '') !== '' ? (string)$data['dob'] : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Candidate SET Name=?, Email=?, Phone=?, Address=?, DOB=? WHERE Candidate_ID=?');
                $stmt->execute([
                    trim((string)($data['name'] ?? '')),
                    trim((string)($data['email'] ?? '')),
                    ($data['phone'] ?? '') !== '' ? trim((string)$data['phone']) : null,
                    ($data['address'] ?? '') !== '' ? trim((string)$data['address']) : null,
                    ($data['dob'] ?? '') !== '' ? (string)$data['dob'] : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Candidate WHERE Candidate_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'jobs':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Job_Posting (Company_Id, Title, Description, Salary_Range, Employment_Type, Posted_Date, Deadline) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([
                    (int)($data['companyId'] ?? 0),
                    trim((string)($data['title'] ?? '')),
                    ($data['desc'] ?? '') !== '' ? trim((string)$data['desc']) : null,
                    ($data['salary'] ?? '') !== '' ? trim((string)$data['salary']) : null,
                    (string)($data['type'] ?? 'Full-Time'),
                    (string)($data['posted'] ?? ''),
                    ($data['deadline'] ?? '') !== '' ? (string)$data['deadline'] : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Job_Posting SET Company_Id=?, Title=?, Description=?, Salary_Range=?, Employment_Type=?, Posted_Date=?, Deadline=? WHERE Job_Id=?');
                $stmt->execute([
                    (int)($data['companyId'] ?? 0),
                    trim((string)($data['title'] ?? '')),
                    ($data['desc'] ?? '') !== '' ? trim((string)$data['desc']) : null,
                    ($data['salary'] ?? '') !== '' ? trim((string)$data['salary']) : null,
                    (string)($data['type'] ?? 'Full-Time'),
                    (string)($data['posted'] ?? ''),
                    ($data['deadline'] ?? '') !== '' ? (string)$data['deadline'] : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Job_Posting WHERE Job_Id=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'applications':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Application (Candidate_ID, Job_ID, Resume_ID, Applied_Date, Status, Cover_Letter) VALUES (?,?,?,?,?,?)');
                $stmt->execute([
                    (int)($data['candidateId'] ?? 0),
                    (int)($data['jobId'] ?? 0),
                    null,
                    (string)($data['date'] ?? ''),
                    (string)($data['status'] ?? 'Pending'),
                    ($data['cover'] ?? '') !== '' ? trim((string)$data['cover']) : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Application SET Candidate_ID=?, Job_ID=?, Applied_Date=?, Status=?, Cover_Letter=? WHERE App_ID=?');
                $stmt->execute([
                    (int)($data['candidateId'] ?? 0),
                    (int)($data['jobId'] ?? 0),
                    (string)($data['date'] ?? ''),
                    (string)($data['status'] ?? 'Pending'),
                    ($data['cover'] ?? '') !== '' ? trim((string)$data['cover']) : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Application WHERE App_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'interviewers':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Interviewer (Company_ID, Name, Email, Department) VALUES (?,?,?,?)');
                $stmt->execute([
                    (int)($data['companyId'] ?? 0),
                    trim((string)($data['name'] ?? '')),
                    trim((string)($data['email'] ?? '')),
                    ($data['dept'] ?? '') !== '' ? trim((string)$data['dept']) : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Interviewer SET Company_ID=?, Name=?, Email=?, Department=? WHERE Interviewer_ID=?');
                $stmt->execute([
                    (int)($data['companyId'] ?? 0),
                    trim((string)($data['name'] ?? '')),
                    trim((string)($data['email'] ?? '')),
                    ($data['dept'] ?? '') !== '' ? trim((string)$data['dept']) : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Interviewer WHERE Interviewer_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'interviews':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Interview (App_ID, Interviewer_ID, Interview_Type, Location_Link, Schedule_Date) VALUES (?,?,?,?,?)');
                $ivrId = ($data['ivrId'] ?? null);
                $stmt->execute([
                    (int)($data['appId'] ?? 0),
                    ($ivrId === null || $ivrId === '' || (int)$ivrId === 0) ? null : (int)$ivrId,
                    ($data['type'] ?? '') !== '' ? trim((string)$data['type']) : null,
                    ($data['loc'] ?? '') !== '' ? trim((string)$data['loc']) : null,
                    from_datetime_local((string)($data['date'] ?? '')),
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $ivrId = ($data['ivrId'] ?? null);
                $stmt = $pdo->prepare('UPDATE Interview SET App_ID=?, Interviewer_ID=?, Interview_Type=?, Location_Link=?, Schedule_Date=? WHERE Interview_ID=?');
                $stmt->execute([
                    (int)($data['appId'] ?? 0),
                    ($ivrId === null || $ivrId === '' || (int)$ivrId === 0) ? null : (int)$ivrId,
                    ($data['type'] ?? '') !== '' ? trim((string)$data['type']) : null,
                    ($data['loc'] ?? '') !== '' ? trim((string)$data['loc']) : null,
                    from_datetime_local((string)($data['date'] ?? '')),
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Interview WHERE Interview_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'offers':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Offer (App_ID, Offered_Salary, Offer_Date, Expiry_Date) VALUES (?,?,?,?)');
                $stmt->execute([
                    (int)($data['appId'] ?? 0),
                    ($data['salary'] ?? null) !== null && $data['salary'] !== '' ? (float)$data['salary'] : null,
                    (string)($data['date'] ?? ''),
                    ($data['expiry'] ?? '') !== '' ? (string)$data['expiry'] : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Offer SET App_ID=?, Offered_Salary=?, Offer_Date=?, Expiry_Date=? WHERE Offer_ID=?');
                $stmt->execute([
                    (int)($data['appId'] ?? 0),
                    ($data['salary'] ?? null) !== null && $data['salary'] !== '' ? (float)$data['salary'] : null,
                    (string)($data['date'] ?? ''),
                    ($data['expiry'] ?? '') !== '' ? (string)$data['expiry'] : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Offer WHERE Offer_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;

        case 'decisions':
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO Hiring_Decision (Offer_ID, Decision, Decision_Date, Reason) VALUES (?,?,?,?)');
                $stmt->execute([
                    (int)($data['offerId'] ?? 0),
                    (string)($data['decision'] ?? 'Pending'),
                    ($data['date'] ?? '') !== '' ? (string)$data['date'] : null,
                    ($data['reason'] ?? '') !== '' ? trim((string)$data['reason']) : null,
                ]);
                json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            if ($action === 'update') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('UPDATE Hiring_Decision SET Offer_ID=?, Decision=?, Decision_Date=?, Reason=? WHERE Decision_ID=?');
                $stmt->execute([
                    (int)($data['offerId'] ?? 0),
                    (string)($data['decision'] ?? 'Pending'),
                    ($data['date'] ?? '') !== '' ? (string)$data['date'] : null,
                    ($data['reason'] ?? '') !== '' ? trim((string)$data['reason']) : null,
                    $id,
                ]);
                json_response(['ok' => true]);
            }
            if ($action === 'delete') {
                if ($id <= 0) json_response(['ok' => false, 'error' => 'Missing id'], 400);
                $stmt = $pdo->prepare('DELETE FROM Hiring_Decision WHERE Decision_ID=?');
                $stmt->execute([$id]);
                json_response(['ok' => true]);
            }
            break;
    }

    json_response(['ok' => false, 'error' => 'Unknown resource/action'], 400);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

