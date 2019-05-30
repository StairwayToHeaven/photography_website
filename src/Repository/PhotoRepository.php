<?php
/**
 * Photo repository.
 *
 * @copyright (c) 2017 Katarzyna Dam
 */


namespace Repository;

use Doctrine\DBAL\Connection;

/**
 * Class PhotoRepository.
 *
 * @package Repository
 * @use Silex\Application
 */
class PhotoRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * PhotoRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    /**
     * Find all photos.
     *
     * @acces public
     * @throws \PDOException
     * @return array Result
     */
    public function findAll()
    {
        try {
            $query = '
              SELECT
                *
              FROM
                si_photos
            ';
            $statement = $this->db->prepare($query);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            return array();
        }
    }
    /**
     * Find photo by id.
     *
     * @acces public
     * @param int $id Photo id
     * @throws \PDOException
     * @return array Result
     */
    public function find($id)
    {
        try {
            $query = '
            SELECT
                *
            FROM
                si_photos
			WHERE
				id = :id;
            ';
            $statement = $this->db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return !$result ? array() : current($result);
        } catch (\PDOException $e) {
            return array();
        }
    }
    /**
     * Save image.
     *
     * @access public
     * @param array  $image     Image data from request
     * @param string $mediaPath Path to media folder on disk
     * @param string $name      Photo name
     * @throws \PDOException
     * @return mixed Result
     */
    public function saveImage($image, $mediaPath, $name)
    {
        try {
            $originalFilename = $image['photo']->getClientOriginalName();
            $newFilename = $this->createName($originalFilename);
            $image['photo']->move($mediaPath, $newFilename);
            $this->saveFilename($newFilename, $name);

            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Delete image.
     *
     * @access public
     * @param integer $id        Photo id
     * @param string  $url       Photo url
     * @param string  $mediaPath Path to media folder on disk
     * @throws \PDOException
     * @return mixed Result
     */
    public function delete($id, $url, $mediaPath)
    {
        try {
            $this->db->delete('si_photos', array('id' => (integer) $id));

            return unlink($mediaPath.$url);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Checks if filename is unique.
     *
     * @access protected
     * @param string $name Name
     * @throws \PDOException
     * @return bool Result
     */
    protected function isUniqueName($name)
    {
        try {
            $query = '
              SELECT
                COUNT(*) as files_count
              FROM
                si_photos
              WHERE
                title = :name
              ';
            $statement = $this->db->prepare($query);
            $statement->bindValue('name', $name, \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $result = current($result);

            return !$result['files_count'];
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Save filename in database.
     *
     * @access protected
     * @param string $name Filename
     * @param string $title Photo title
     * @return mixed Result
     */
    protected function saveFilename($name, $title)
    {

        return $this->db->insert(
            'si_photos',
            array(
                'url' => $name,
                'title' => $title,
            )
        );
    }
    /**
     * Creates random filename.
     *
     * @access protected
     * @param string $name Source filename
     *
     * @return string Result
     */
    protected function createName($name)
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = $this->createRandomString(20).'.'.$ext;

        while (!$this->isUniqueName($newName)) {
            $newName = $this->createRandomString(20).'.'.$ext;
        }

        return $newName;
    }

    /**
     * Creates random string.
     *
     * @acces protected
     * @param integer $length String length
     *
     * @return string Result
     */
    protected function createRandomString($length)
    {
        $string = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
            $string .= $keys[array_rand($keys)];
        }

        return $string;
    }
}
