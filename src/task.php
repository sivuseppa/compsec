<?php
/**
 * The model of the Task
 *
 * @package MMoro\CompSecApp
 */

namespace MMoro\CompSecApp;

use SQLite3;

/**
 * The Task class
 */
final class Task {

	private static $db;

	public $id          = null;
	public $name        = '';
	public $description = '';
	public $status      = 'TODO';
	public $author_id;

	/**
	 * Constuctor
	 *
	 * @param int $task_id The id of the task.
	 */
	public function __construct( $task_id = null ) {
		$this->id = $task_id;
	}

	public static function set_db( $db ) {
		self::$db = $db;
	}

	/**
	 * Get data of the task.
	 */
	public function get_taskdata() {

		if ( ! $this->name ) {
			$statement = self::$db->prepare( 'SELECT * FROM "tasks" WHERE "id" = ?' );
			$statement->bindValue( 1, $this->id );
			$result    = $statement->execute();
			$task_data = $result->fetchArray( SQLITE3_ASSOC );

			$this->name        = $task_data['name'];
			$this->description = $task_data['description'];
			$this->status      = $task_data['status'];
			$this->author_id   = $task_data['author_id'];
		}

		return array(
			'id'          => $this->id,
			'name'        => $this->name,
			'description' => $this->description,
			'status'      => $this->status,
			'author_id'   => $this->author_id,
		);
	}

	/**
	 * Get all tasks from the database.
	 *
	 * @param Object $user the author of the taks
	 */
	public static function get_tasks( $user ) {

		$results_arr = array();

		if ( $user->is_admin() ) {
			// $results = self::$db->query( 'SELECT id, name, description, status, author_id FROM "tasks"' );
			$results = self::$db->query( 'SELECT * FROM "tasks"' );

		} else {
			$statement = self::$db->prepare( 'SELECT * FROM "tasks" WHERE "author_id" = ?' );
			$statement->bindValue( 1, $user->id );
			$results = $statement->execute();
		}

		while ( $result = $results->fetchArray( SQLITE3_ASSOC ) ) {
			$results_arr[] = $result;
		}

		$results->finalize();
		// new Logger()->write( $results_arr );
		return $results_arr;
	}

	/**
	 * Save task to the database.
	 */
	public function save() {

		// Insert potentially unsafe data with a prepared statement and named parameters.
		if ( $this->id ) {
			$statement = self::$db->prepare(
				'UPDATE tasks 
				SET name = :name, description = :description, status = :status, author_id = :author_id
				WHERE id = :id'
			);
		} else {
			$statement = self::$db->prepare(
				'INSERT INTO tasks ("id", "name", "description", "status", "author_id")
				VALUES (:id, :name, :description, :status, :author_id)'
			);
		}

		$statement->bindValue( ':id', $this->id );
		$statement->bindValue( ':name', $this->name );
		$statement->bindValue( ':description', $this->description );
		$statement->bindValue( ':status', $this->status );
		$statement->bindValue( ':author_id', $this->author_id );
		$statement->execute();

		// Test how userdata is saved, remove this!!!
		// $statement = self::$db->prepare( 'SELECT * FROM "users" WHERE "id" = ?' );
		// $statement->bindValue( 1, $this->id );
		// $result    = $statement->execute();
		// $user_data = $result->fetchArray( SQLITE3_ASSOC );
		// new Logger()->write( $user_data );

		send_response_and_exit( 200, 'success', 'Task saved.' );
	}

	/**
	 * Delete task.
	 */
	public function delete() {
		$statement = self::$db->prepare(
			'DELETE FROM "tasks"
			WHERE id = :id'
		);
		$statement->bindValue( ':id', $this->id );
		$statement->execute();
		send_response_and_exit( 200, 'success', 'Task deleted.' );
	}
}
