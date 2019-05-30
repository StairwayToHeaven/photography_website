<?php
/**
 * Comment repository
 *
 * @category Repository
 * @copyright (c) 2017 Katarzyna Dam
 */

namespace Repository;

use Doctrine\DBAL\Connection;

/**
 * Class CommentRepository.
 *
 * @uses Doctrine\DBAL\Connection
 * @package Repository
 */
class CommentRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * CommentRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Gets comment by id.
     *
     * @access public
     * @param int $id Comment id
     * @throws \PDOException
     * @return array Result
     */
    public function findById($id)
    {
        try {
            $query = '
              SELECT
                si_comments.id AS id, content, date, si_user_info.name AS name
              FROM
                si_comments
			  JOIN
				si_user_info
			  ON
				si_comments.user_id = si_user_info.user_id
              WHERE
                si_comments.id = :id
            ';

            $statement = $this->db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();

            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $result = current($result);

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            return array();
        }
    }

    /**
     * Save comment.
     *
     * @access public
     * @param array $comment Comment data
     * @throws \PDOException
     */
    public function save($comment)
    {
        try {
            $this->db->beginTransaction();
            if (isset($comment['id'])
                && $comment['id'] != ''
                && ctype_digit((string) $comment['id'])
            ) {
                $id = $comment['id'];
                unset($comment['id']);
                $this->db->update(
                    'si_comments',
                    array(
                        'content' => $comment['content'],
                    ),
                    array('id' => $id)
                );
                $comment['comment_id'] = $id;
            } else {
                $this->db->insert(
                    'si_comments',
                    array(
                        'content' => $comment['content'],
                        'user_id' => $comment['user_id'],
                    )
                );
                $comment['comment_id'] = $this->db->lastInsertId();
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Gets colection comment.
     *
     * @access public
     * @throws \PDOException
     * @return array Result
     */
    public function findAll()
    {
        try {
            $query = '
				SELECT 
					si_comments.id AS id, content, date, name
				FROM 
					si_comments
				JOIN
					si_user_info
				ON
					si_user_info.user_id = si_comments.user_id
				ORDER BY
					date
				DESC
			';
            $statement = $this->db->prepare($query);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Gets colection comment from user.
     *
     * @access public
     * @param integer $id User id
     * @throws \PDOException
     * @return array Result
     */
    public function findAllFromUser($id)
    {
        try {
            $query = '
				SELECT 
					id
				FROM 
					si_comments
				WHERE
					user_id = :id
			';
            $statement = $this->db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Delete comment.
     *
     * @access public
     * @param array $comment Comment data
     * @throws \PDOException
     */
    public function delete($comment)
    {
        try {
                $this->db->delete(
                    'si_comments',
                    array('id' => $comment['id'])
                );
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Gets comment by id to edit.
     *
     * @access public
     * @param int $id Comment id
     * @throws \PDOException
     * @return array Result
     */
    public function findToEdit($id)
    {
        try {
            $query = '
              SELECT
                si_comments.id AS id, content, name
              FROM
                si_comments
			  JOIN 
				si_user_info
			  ON
				si_comments.user_id = si_user_info.user_id
              WHERE
                si_comments.id = :id
            ';

            $statement = $this->db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();

            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $result = current($result);

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            return array();
        }
    }
}
