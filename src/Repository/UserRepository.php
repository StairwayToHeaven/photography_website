<?php
/**
 * User repository
 *
 * @category Repository
 * @copyright (c) 2017 Katarzyna Dam
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class UserRepository.
 *
 * @uses Doctrine\DBAL\Connection
 * @uses Doctrine\DBAL\DBALException
 * @uses Symfony\Component\Security\Core\Exception\UsernameNotFoundException
 * @package Repository
 */
class UserRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * UserRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                         ->from('si_users', 'u')
                         ->where('u.login = :login')
                         ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('si_users', 'u')
                ->innerJoin('u', 'si_roles', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }
    /**
     * Gets user by id.
     *
     * @access public
     * @param int $id User id
     * @throws \PDOException
     * @return array Result
     */
    public function findById($id)
    {
        try {
            $query = '
              SELECT
                *
              FROM
                si_users
              WHERE
                id = :id
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
     * Check if login is unique.
     *
     * @param string $login User Login
     *
     * @return bool
     */
    public function loginUnique($login)
    {
        $query = '
                    SELECT 
                        count(*) AS count
                    FROM 
                        si_users 
                    WHERE
                        login = :login;
				';
                $statement = $this->db->prepare($query);
                $statement->bindValue('login', $login, \PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if ($result[0]['count'] == 0) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Check if login is unique in edit action.
     *
     * @param array $user User array
     *
     * @return bool
     */
    public function loginUniqueInEdit($user)
    {
        $query = '
                    SELECT 
                        count(*) AS count
                    FROM 
                        si_users 
                    WHERE
                        login = :login
					AND
						id <> :id;
				';
                $statement = $this->db->prepare($query);
                $statement->bindValue('login', $user['login'], \PDO::PARAM_STR);
                $statement->bindValue('id', $user['id'], \PDO::PARAM_INT);
                $statement->execute();
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if ($result[0]['count'] == 0) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Save user.
     *
     * @access public
     * @param array $user User data
     * @throws \PDOException
     */
    public function save($user)
    {
        try {
            $this->db->beginTransaction();
            if (isset($user['id'])
                && $user['id'] != ''
                && ctype_digit((string) $user['id'])
            ) {
                // update record
                $id = $user['id'];
                unset($user['id']);
                $pass = $user['app']['security.encoder.bcrypt']->encodePassword($user['password'], '');
                $this->db->update(
                    'si_users',
                    array(
                        'login' => $user['login'],
                        'password' => $pass,
                        'role_id' => $user['role_id'],
                    ),
                    array('id' => $id)
                );
                $user['user_id'] = $id;
                $this->saveUserInfo($user);
            } else {
                // add new record
                $pass = $user['app']['security.encoder.bcrypt']->encodePassword($user['password'], '');
                $this->db->insert(
                    'si_users',
                    array(
                        'login' => $user['login'],
                        'role_id' => $user['role_id'],
                        'password' => $pass,
                    )
                );
                $user['user_id'] = $this->db->lastInsertId();
                $this->saveUserInfo($user);
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Save user info.
     *
     * @access public
     * @param array $user User data
     * @throws \PDOException
     */
    public function saveUserInfo($user)
    {
        try {
            $this->db->beginTransaction();
            if (isset($user['info_id'])
                && $user['info_id'] != ''
                && ctype_digit((string) $user['info_id'])
            ) {
                $id = $user['info_id'];
                unset($user['info_id']);
                $this->db->update(
                    'si_user_info',
                    array(
                        'name' => $user['name'],
                        'mail' => $user['mail'],
                        'user_id' => $user['user_id'],
                    ),
                    array('id' => $id)
                );
            } else {
                // add new record
                $this->db->insert(
                    'si_user_info',
                    array(
                        'name' => $user['name'],
                        'mail' => $user['mail'],
                        'user_id' => $user['user_id'],
                    )
                );
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Gets colection users.
     *
     * @access public
     * @param integer $page  Current page
     * @param integer $limit Pagination limit
     * @throws \PDOException
     * @return array Result
     */
    public function findAll($page, $limit)
    {
        try {
            $start = ($page - 1)*$limit;
            $query = '
				SELECT 
					id, login, role_id
				FROM 
					si_users 
				LIMIT 
					:start, :limit;
			';
            $statement = $this->db->prepare($query);
            $statement->bindValue('start', $start, \PDO::PARAM_INT);
            $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
            $statement->execute();
            $result['collection'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $count = $this->counter();
            $result['pages'] = ceil((integer) $count['count'] / (integer) $limit);
            $result['page'] = $page;

            return !$result ? array() : $result;
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Counter users.
     *
     * @access public
     * @throws \PDOException
     * @return array Result
     */
    public function counter()
    {
        try {
            $query = '
				SELECT 
					count(*) AS count
				FROM 
					si_users;
			';
            $statement = $this->db->prepare($query);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return !$result ? array() : current($result);
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Delete user.
     *
     * @access public
     * @param array $user User data
     * @throws \PDOException
     */
    public function delete($user)
    {
        try {
                $commentModel = new CommentRepository($this->db);
                $comments = $commentModel->findAllFromUser($user['id']);
            foreach ($comments as $comment) {
                $commentModel->delete($comment);
            }
                $this->db->delete(
                    'si_users',
                    array('id' => $user['id'])
                );
                $this->db->delete(
                    'si_user_info',
                    array('user_id' => $user['id'])
                );
        } catch (\PDOException $e) {
            throw $e;
        }
    }
    /**
     * Gets user by id to edit.
     *
     * @access public
     * @param int $id User id
     * @throws \PDOException
     * @return array Result
     */
    public function findToEdit($id)
    {
        try {
            $query = '
              SELECT
                si_users.id AS id, si_users.login AS login, si_users.password AS password,
				si_users.role_id AS role_id, si_user_info.name AS name, si_user_info.mail AS mail, 
				si_user_info.id AS info_id
              FROM
                si_users
			  JOIN
			    si_user_info
			  ON
				si_users.id = si_user_info.user_id
              WHERE
                si_users.id = :id
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
     * Gets user id by login.
     *
     * @access public
     * @param string $login User login
     * @throws \PDOException
     * @return array Result
     */
    public function getUserIdByLogin($login)
    {
        try {
            $query = '
              SELECT
                id
              FROM
                si_users
              WHERE
                login = :login 
            ';

            $statement = $this->db->prepare($query);
            $statement->bindValue('login', $login, \PDO::PARAM_STR);
            $statement->execute();

            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $result = current($result);

            return !$result ? 0 : $result['id'];
        } catch (\PDOException $e) {
            return 0;
        }
    }
}
