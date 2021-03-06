<?php


namespace phpCollab\Files;

use phpCollab\Database;

/**
 * Class FilesGateway
 * @package phpCollab\Files
 */
class FilesGateway
{
    protected $db;
    protected $initrequest;
    protected $tableCollab;

    /**
     * FilesGateway constructor.
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->initrequest = $GLOBALS['initrequest'];
        $this->tableCollab = $GLOBALS['tableCollab'];
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getFiles($fileId)
    {
        if (strpos($fileId, ',')) {
            $ids = explode(',', $fileId);
            $placeholders = str_repeat('?, ', count($ids) - 1) . '?';
            $sql = $this->initrequest["files"] . " WHERE fil.id IN ($placeholders) OR vc_parent IN ($placeholders)";
            $this->db->query($sql);

            $this->db->execute(array_merge($ids, $ids));

            return $this->db->fetchAll();
        } else {
            $query = $this->initrequest["files"] . " WHERE fil.id IN(:file_id) OR fil.vc_parent IN(:file_id) ORDER BY fil.name";

            $this->db->query($query);

            $this->db->bind(':file_id', $fileId);

            return $this->db->resultset();
        }
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getFileById($fileId)
    {
        $query = $this->initrequest["files"] . " WHERE fil.id IN(:file_id) OR fil.vc_parent IN(:file_id) ORDER BY fil.name";
        $this->db->query($query);
        $this->db->bind(':file_id', $fileId);
        return $this->db->single();
    }

    /**
     * @param $taskId
     * @param null $sorting
     * @return mixed
     */
    public function getFilesByTaskIdAndVCParentEqualsZero($taskId, $sorting = null)
    {
        $query = $this->initrequest["files"] . " WHERE fil.task = :task_id AND fil.vc_parent = 0" . $this->orderBy($sorting);
        $this->db->query($query);
        $this->db->bind(':task_id', $taskId);
        return $this->db->resultset();
    }

    /**
     * @param $projectId
     * @return mixed
     */
    public function getFilesByProjectIdAndPhaseNotEqualZero($projectId)
    {
        $query = $this->initrequest["files"] .  " WHERE fil.project = :project_id AND fil.phase !='0'";
        $this->db->query($query);
        $this->db->bind(':project_id', $projectId);
        return $this->db->resultset();
    }

    /**
     * @param $projectId
     * @param $phaseId
     * @param null $sorting
     * @return mixed
     */
    public function getFilesByProjectAndPhaseWithoutTasksAndParent($projectId, $phaseId, $sorting = null)
    {
        $whereStatement = "WHERE fil.project = :project_id AND fil.phase = :phase_id AND fil.task = 0 AND fil.vc_parent = 0";// ORDER BY {$block3->sortingValue}";
        $query = $this->initrequest["files"] . $whereStatement . $this->orderBy($sorting);
        $this->db->query($query);
        $this->db->bind(':project_id', $projectId);
        $this->db->bind(':phase_id', $phaseId);
        return $this->db->resultset();

    }

    /**
     * @return mixed
     */
    public function getPublishedFiles()
    {
        $query = $this->initrequest["files"] . " WHERE fil.published = 0";
        $this->db->query($query);
        return $this->db->resultset();
    }

    /**
     * @return mixed
     */
    public function getUnPublishedFiles()
    {
        $query = $this->initrequest["files"] . " WHERE fil.published = 1";
        $this->db->query($query);
        return $this->db->resultset();
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function deleteFiles($fileId)
    {

        if (strpos($fileId, ',')) {
            $ids = explode(',', $fileId);
            $placeholders = str_repeat('?, ', count($ids) - 1) . '?';
            $sql = "DELETE FROM {$this->tableCollab['files']} WHERE id IN ($placeholders) OR vc_parent IN($placeholders)";
            $this->db->query($sql);

            $this->db->execute(array_merge($ids, $ids));

            return $this->db->fetchAll();
        } else {
            $query = "DELETE FROM {$this->tableCollab['files']} WHERE id IN (:file_id) OR vc_parent IN(:file_id)";

            $this->db->query($query);

            $this->db->bind(':file_id', $fileId);

            return $this->db->execute();
        }
    }

    /**
     * @param $projectIds
     * @return mixed
     */
    public function deleteFilesByProjectId($projectIds)
    {
        $projectId = explode(',', $projectIds);
        $placeholders = str_repeat('?, ', count($projectId) - 1) . '?';
        $sql = "DELETE FROM {$this->tableCollab['files']} WHERE project IN ($placeholders)";
        $this->db->query($sql);
        return $this->db->execute($projectId);
    }


    /**
     * @param $fileId
     * @param $fileStatus
     * @return mixed
     */
    public function getFileVersions($fileId, $fileStatus)
    {
        $query = $this->initrequest["files"] . " WHERE fil.id = :file_id OR fil.vc_parent = :file_id AND fil.vc_status = :file_status ORDER BY fil.date DESC";
        $this->db->query($query);
        $this->db->bind(':file_id', $fileId);
        $this->db->bind(':file_status', $fileStatus);

        return $this->db->resultset();
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getFilePeerReviews($fileId)
    {
        $query = $this->initrequest["files"] . " WHERE fil.vc_parent = :file_id AND fil.vc_status != 3 " . $this->orderBy('fil.date');
        $this->db->query($query);
        $this->db->bind(':file_id', $fileId);
        return $this->db->resultset();
    }


    /**
     * @param $fileIds
     * @return mixed
     */
    public function publishFiles($fileIds)
    {
        if (strpos($fileIds, ',')) {
            $fileIds = explode(',', $fileIds);
            $placeholders = str_repeat('?, ', count($fileIds) - 1) . '?';
            $sql = "UPDATE {$this->tableCollab['files']} SET published = 0 WHERE id IN ($placeholders)";
            $this->db->query($sql);
            return $this->db->execute($fileIds);
        } else {
            $sql = "UPDATE {$this->tableCollab['files']} SET published = 0 WHERE id = :topic_ids";
            $this->db->query($sql);
            $this->db->bind(':topic_ids', $fileIds);
            return $this->db->execute();
        }
    }

    /**
     * @param $fileIds
     * @return mixed
     */
    public function publishFilesByIdOrInVcParent($fileIds)
    {
        $fileIds = explode(',', $fileIds);
        $placeholders = str_repeat('?, ', count($fileIds) - 1) . '?';
        $placeholders2 = $placeholders;
        $sql = "UPDATE {$this->tableCollab['files']} SET published = 1 WHERE id IN ($placeholders) OR vc_parent IN ($placeholders2)";
        $this->db->query($sql);
        return $this->db->execute([$fileIds, $fileIds]);
    }

    /**
     * @param $fileIds
     * @return mixed
     */
    public function unPublishFilesByIdOrInVcParent($fileIds)
    {
        $fileIds = explode(',', $fileIds);
        $placeholders = str_repeat('?, ', count($fileIds) - 1) . '?';
        $placeholders2 = $placeholders;
        $sql = "UPDATE {$this->tableCollab['files']} SET published = 0 WHERE id IN ($placeholders) OR vc_parent IN ($placeholders2)";
        $this->db->query($sql);
        return $this->db->execute([$fileIds, $fileIds]);
    }

    /**
     * @param $fileIds
     * @return mixed
     */
    public function unPublishFiles($fileIds)
    {
        if (strpos($fileIds, ',')) {
            $fileIds = explode(',', $fileIds);
            $placeholders = str_repeat('?, ', count($fileIds) - 1) . '?';
            $sql = "UPDATE {$this->tableCollab['files']} SET published = 1 WHERE id IN ($placeholders)";
            $this->db->query($sql);
            return $this->db->execute($fileIds);
        } else {
            $sql = "UPDATE {$this->tableCollab['files']} SET published = 1 WHERE id = :topic_ids";
            $this->db->query($sql);
            $this->db->bind(':topic_ids', $fileIds);
            return $this->db->execute();
        }
    }


    /**
     * @param string $sorting
     * @return string
     */
    private function orderBy($sorting)
    {
        if (!is_null($sorting)) {
            $allowedOrderedBy = ["fil.type", "fil.name", "fil.owner", "fil.date", "fil.approval_tracking", "fil.published"];
            $pieces = explode(' ', $sorting);

            if ($pieces) {
                $key = array_search($pieces[0], $allowedOrderedBy);

                if ($key !== false) {
                    $order = $allowedOrderedBy[$key];
                    return " ORDER BY $order $pieces[1]";
                }
            }
        }

        return '';
    }
}