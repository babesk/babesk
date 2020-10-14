<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';

class Inventory extends Schbas {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////
	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if(isset($_GET['getBooksForBarcodes'])) {
			if(isset($_GET['barcodes']) && count($_GET['barcodes'])) {
				$this->booksForBarcodesSend($_GET['barcodes']);
			}
		}
		else if(isset($_GET['ajax'])) {
			$this->index();
		}
		else if(isset($_GET['id']) && isset($_POST['exemplar'])) {
			$id = filter_input(INPUT_GET, 'id');
			$this->update($id, $_POST);
		}
		else if(isset($_GET['id'])) {
			$id = filter_input(INPUT_GET, 'id');
			$this->edit($id);
		}
		else {
			$this->indexHtml();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->moduleTemplatePathSet();
	}

	/**
	 * Returns the barcodes and, if it fits to more than one book, the books
	 * Dies with the following structure: {
	 *     unique: [ {barcode: <barcodeString>, bookId: <bookId> } ],
	 *     duplicated: [
	 *         {
	 *             books: [
	 *                 {id: <bookId>, title: <bookTitle> }
	 *             ],
	 *             barcodes: [ <barcodeString> ]
	 *         }
	 *     ]
	 *
	 * }
	 *
	 * @param  array  $barcodes An array of strings representing barcodes
	 */
	protected function booksForBarcodesSend($barcodeStrings) {

		$extractIdsFromBooks = function($book) {
			return $book->getId();
		};

		$uniqueBarcodes = [];
		$duplicatedBarcodes = [];
		foreach($barcodeStrings as $barcodeStr) {
			$barcode = new \Babesk\Schbas\Barcode();
			if($barcode->initByBarcodeString($barcodeStr)) {
				// SQL-Request for every barcode, dont request too many :P
				$books = $barcode->getMatchingBooks($this->_em);
				if(count($books) == 1) {
					$uniqueBarcodes[] = [
						'barcode' => $barcodeStr,
						'bookId' => $books[0]->getId()
					];
				}
				else {
					// Make the combined book-ids the key of the array to group
					// the duplicated barcodes
					$bookIds = array_map($extractIdsFromBooks, $books);
					asort($bookIds);
					$key = implode('_', $bookIds);
					if(!isset($duplicatedBarcodes[$key])) {
						$duplicatedBarcodes[$key] = [
							'books' => [],
							'barcodes' => []
						];
						foreach($books as $book) {
							$duplicatedBarcodes[$key]['books'][] = [
								'id' => $book->getId(),
								'title' => $book->getTitle()
							];
						}
					}
					$duplicatedBarcodes[$key]['barcodes'][] = $barcodeStr;
				}
			}
			else {
				dieHttp("Barcode '$barcodeStr' inkorrekt", 400);
			}
		}
		// Remove the keys so that it is a json-array
		$duplicatedBarcodes = array_values($duplicatedBarcodes);
		dieJson( [
			'unique' => $uniqueBarcodes,
			'duplicated' => $duplicatedBarcodes
		] );
	}

	/*==========  index  ==========*/

	/**
	 * Send the basic HTML-page to the user
	 */
	protected function indexHtml() {

		$this->displayTpl('index.tpl');
	}

	/**
	 * Show the inventory-list
	 */
	protected function index() {

		$sort = filter_input(INPUT_GET, 'sort');
		$filter = filter_input(INPUT_GET, 'filter');
		$activePage = filter_input(INPUT_GET, 'activePage',
			FILTER_VALIDATE_INT);
		$entriesPerPage = filter_input(INPUT_GET, 'entriesPerPage',
			FILTER_VALIDATE_INT);
		$displayColumns = [];
		if(isset($_GET['displayColumns']) && count($_GET['displayColumns'])) {
			$displayColumns = array_map(function($columnString) {
				return filter_var($columnString, FILTER_SANITIZE_STRING);
			}, $_GET['displayColumns']);
		}
		if(
			!isset($sort) || !isset($filter) || !isset($activePage) ||
			!isset($entriesPerPage) || !count($displayColumns)
		) {
			dieHttp('Fehlende Parameter', 400);
		}
		else {
			$this->sendIndex(
				$sort, $filter, $activePage, $entriesPerPage, $displayColumns
			);
		}
	}

	protected function sendIndex(
		$sort, $filter, $activePage, $entriesPerPage, $displayColumns
	) {
		$this->sendIndexCheckInput($entriesPerPage);
		$stmt = $this->_pdo->prepare("SELECT * FROM SchbasInventory i LEFT JOIN SchbasLending l ON (i.id = l.inventory_id) LEFT JOIN SystemUsers u ON (l.user_id = u.ID)");
		$stmt->execute();
		$result = $stmt->fetchAll();

		$stmtRowCount = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasInventory");
		$stmtRowCount->execute();
		$rowCount = $stmtRowCount->fetch()[0];
		/**$qb = $this->_em->createQueryBuilder()
			->select(['i', 'b', 's'])
			->from('DM:SchbasInventory', 'i')
			->leftJoin('i.book', 'b')
			->leftJoin('b.subject', 's')
			->leftJoin('i.lending', 'l')
			->leftJoin('l.user', 'u');
		$rowCountQb = $this->_em
			->createQueryBuilder()
			->select('COUNT(DISTINCT i.id)')
			->from('DM:SchbasInventory', 'i')
			->leftJoin('i.book', 'b')
			->leftJoin('b.subject', 's')
			->leftJoin('i.lending', 'l')
			->leftJoin('l.user', 'u');*/
		/**if(!empty($filter)) {
			$this->sendIndexApplyFilter($filter, $qb, $displayColumns);
			$this->sendIndexApplyFilter($filter, $rowCountQb, $displayColumns);
		}
		$qb->setFirstResult(($activePage - 1) * $entriesPerPage)
			->setMaxResults($entriesPerPage);*/

		$data = $this->sendIndexCreateAnswerData(
			$result, $rowCount, $entriesPerPage
		);

		dieJson($data);
	}

	protected function sendIndexCheckInput($entriesPerPage) {

		if($entriesPerPage < 0 || $entriesPerPage > 1000) {
			dieHttp('Inkorrekte Eingabe: Einträge pro Seite', 400);
		}
	}

	protected function sendIndexApplyFilter(
		$filter, $queryBuilder, $displayColumns
	) {
		if(in_array('barcode', $displayColumns)) {
			$queryBuilder->where('i.yearOfPurchase LIKE :filter')
				->orWhere('b.class LIKE :filter')
				->orWhere('b.bundle LIKE :filter')
				->orWhere('i.exemplar LIKE :filter')
				->orWhere('s.abbreviation LIKE :filter');
		}
		if(in_array('lentUser', $displayColumns)) {
			$queryBuilder->orWhere('u.username LIKE :filter');
		}
		if(in_array('bookTitle', $displayColumns)) {
			$queryBuilder->orWhere('b.title LIKE :filter');
		}
		if(in_array('bookIsbn', $displayColumns)) {
			$queryBuilder->orWhere('b.isbn LIKE :filter');
		}
		if(in_array('bookAuthor', $displayColumns)) {
			$queryBuilder->orWhere('b.author LIKE :filter');
		}
		if(in_array('subjectName', $displayColumns)) {
			$queryBuilder->orWhere('s.name LIKE :filter');
		}
		if(in_array('id', $displayColumns)) {
			$queryBuilder->orWhere('i.id LIKE :filter');
		}
		$queryBuilder->setParameter('filter', "%$filter%");
	}

	protected function sendIndexCreateAnswerData(
		$result, $rowCount, $entriesPerPage
	) {

		$data = [];
		/**foreach($result as $row) {
			$rowData = [];
			$rowData['id'] = $row->getId();
			if($row['lend_date']) {
				$user = $row->getLending()->first()->getUser();
				$rowData['lentUser'] = [
					'id' => $user->getId(),
					'username' => $user->getUsername()
				];
				$rowData['lentUserId'] = $user->getId();
				$rowData['lentUserUsername'] = $user->getUsername();
			}
			if($row->getBook()) {
				if($row->getBook()->getSubject()) {
					$barcode = Babesk\Schbas\Barcode::createByInventory($row);
					$rowData['barcode'] = ($barcode) ?
						$barcode->getAsString() : '???';
					$rowData['subjectName'] = $row->getBook()->
						getSubject()->getName();
				}
				else {
					$this->_logger->logO('Subject for inventory not found',
						['sev' => 'error', 'moreJson' => ['invId' =>
						$row->getId()]]);
					$rowData['barcode'] = 'Fach nicht gefunden!';
				}
				$rowData['bookTitle'] = $row->getBook()->getTitle();
				$rowData['bookIsbn'] = $row->getBook()->getIsbn();
				$rowData['bookAuthor'] = $row->getBook()->getAuthor();
			}
			else {
				$this->_logger->logO('Book for inventory not found', ['sev' =>
					'error', 'moreJson' => ['invId' => $row->getId()]]);
				$rowData['barcode'] = 'Buch nicht gefunden!';
			}
			$data['data'][] = $rowData;
		}*/
		$data['data'] = $result;
		$data['pageCount'] = ceil($rowCount / $entriesPerPage);
		return $data;
	}

	/*==========  edit  ==========*/

	protected function edit($inventoryId) {

		$qb = $this->_em->createQueryBuilder()
			->select(['i', 'b', 's'])
			->from('DM:SchbasInventory', 'i')
			->leftJoin('i.book', 'b')
			->leftJoin('b.subject', 's')
			->leftJoin('i.lending', 'l')
			->leftJoin('l.user', 'u');
		$qb->where('i.id = :inventoryId');
		$qb->setParameter('inventoryId', $inventoryId);
		$inventory = $qb->getQuery()->getOneOrNullResult();
		if(!$inventory) {
			$this->_interface->dieMsg('Das Inventar wurde nicht gefunden.');
		}
		$this->_smarty->assign('inventory', $inventory);
		$this->displayTpl('edit.tpl');
	}

	/*==========  update  ==========*/


	protected function update($inventoryId, $data) {

		$yearOfPurchase = $data['year-of-purchase'];
		$exemplar = $data['exemplar'];
		$inventory = $this->_em->find('DM:SchbasInventory', $inventoryId);
		if(!$inventory) {
			$this->_interface->dieError('Inventar nicht gefunden');
		}
		$inventory->setYearOfPurchase($yearOfPurchase);
		$inventory->setExemplar($exemplar);
		$this->_em->persist($inventory);
		$this->_em->flush();
		$this->_interface->dieSuccess('Inventar erfolgreich verändert');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>
