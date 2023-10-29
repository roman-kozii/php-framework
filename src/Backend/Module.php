<?php

namespace Nebula\Backend;

use Nebula\Alerts\Flash;
use Nebula\Database\QueryBuilder;
use Nebula\Traits\Http\Response;
use Nebula\Validation\Validate;
use Nebula\Backend\FormControls;
use PDOException;

class Module
{
    use Response;

    /** Module */
    protected string $module_name;
    protected string $module_title;
    protected ?string $table_name;
    protected string $name_col = "name";
    protected string $key_col = "id";
    protected bool $table_create = true;
    protected bool $table_edit = true;
    protected bool $table_destroy = true;
    /** Form */
    protected bool $edit_view = true;
    protected bool $create_view = true;
    protected array $form_columns = [];
    protected array $form_data = [];
    protected array $file_extensions = [
        ".txt",
        ".pdf",
        ".doc",
        ".docx",
        ".xls",
        ".xlsx",
        ".ppt",
        ".pptx",
        ".csv",
        ".zip",
        ".rar",
        ".7z",
        ".tar",
        ".gz",
        ".mp3",
        ".wav",
        ".mp4",
        ".mov",
        ".avi",
    ];
    protected array $image_extensions = [
        ".jpg",
        ".jpeg",
        ".png",
        ".gif",
        ".bmp",
        ".tif",
        ".tiff",
        ".webp",
        ".svg",
        ".ico",
    ];
    /** Controls **/
    protected array $form_controls = [];
    protected array $select_options = [];
    /** Table */
    protected bool $table_view = true;
    protected array $table_columns = [];
    protected array $table_data = [];
    protected array $search = [];
    protected array $filter_links = [];
    protected string $filter_link = '';
    protected array $where = [];
    protected string $order_by = "";
    protected string $sort = "DESC";
    /** Pagination */
    protected int $page = 1;
    protected int $total_results = 0;
    protected int $total_pages = 1;
    protected int $limit = 15;
    protected ?int $offset = null;
    /** Validation */
    protected array $validation = [];
    protected array $errors = [];

    public function __construct(string $module_name, ?string $table_name = null)
    {
        $this->module_name = $module_name;
        $this->module_title = ucfirst($module_name);
        $this->table_name = $table_name;
    }

    /**
     * Process request for index view
     */
    protected function processTableRequest(): void
    {
        $this->handleOrdering();
        $this->handlePagination();
        $this->handleSearch();
        $this->handleFilterCount();
        $this->handleFilterLinks();
    }

    /**
     * Process request for edit / create views
     */
    protected function processFormRequest(?string $id = null): void
    {
        $this->handleDeleteFile($id);
    }

    /**
     * Handle uploading a file
     */
    protected function handleUpload(string $id): bool
    {
        foreach (request()->files() as $column => $file) {
            $timestamp = time();
            $random = md5(uniqid());
            $filename = $file["name"];
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $new_filename = sprintf(
                "%s_%s.%s",
                $timestamp,
                $random,
                $extension
            );
            $uploads_path = config("paths.uploads");
            $target_path = $uploads_path . $new_filename;
            if (
                !file_exists($target_path) &&
                move_uploaded_file($file["tmp_name"], $target_path)
            ) {
                $this->deleteColumnFile($column, $id);
                $qb = QueryBuilder::update($this->table_name)
                    ->columns([$column => $target_path])
                    ->where([$this->key_col, $id]);
                if (is_null(db()->run($qb->build(), $qb->values()))) {
                    return false;
                }
                $this->auditColumns([$column => $target_path], $id, 'UPLOAD');
            }
        }
        return true;
    }

    /**
     * Handle deleting an uploaded file
     */
    protected function handleDeleteFile(?string $id): void
    {
        if (request()->has("delete_file")) {
            $column = request()->delete_file;
            $this->deleteColumnFile($column, $id);
            $qb = QueryBuilder::update($this->table_name)
                ->columns([$column => null])
                ->where([$this->key_col, $id]);
            if (is_null(db()->run($qb->build(), $qb->values()))) {
                Flash::addFlash(
                    "danger",
                    "Oops! An unknown issue occurred while deleting file"
                );
            }
            redirectModule("module.edit", $this->module_name, $id);
        }
    }

    /**
     * Handle deleting a file from a column
     */
    protected function deleteColumnFile(string $column, string $id): bool
    {
        $row = db()->select(
            "SELECT $column FROM $this->table_name WHERE $this->key_col = ?",
            $id
        );
        if (
            $row &&
            !is_null($row->$column) &&
            trim($row->$column) != "" &&
            file_exists($row->$column)
        ) {
            return unlink($row->$column);
        }
        return false;
    }

    /**
     * Handle filter count request
     */
    protected function handleFilterCount(): void
    {
        if (request()->has("filter_count")) {
            $idx = request()->filter_count;
            if (isset($this->filter_links[$idx])) {
                $this->where[] = [$this->filter_links[$idx]];
                $count = $this->getTotalResults();
                echo json($count);
            }
            exit();
        }
    }

    /**
     * Handle filter link request
     */
    protected function handleFilterLinks(): void
    {
        if (request()->has("filter_link")) {
            $idx = request()->filter_link;
            if (isset($this->filter_links[$idx])) {
                session()->set(
                    $this->module_name . "_filter_link",
                    $this->filter_links[$idx]
                );
            }
        }

        if (session()->has($this->module_name . "_filter_link")) {
            $filter = session()->get($this->module_name . "_filter_link");
            // Store the title (key) as the active filter_link
            $this->filter_link = array_search($filter, $this->filter_links);
            $this->where[] = [$filter];
        }
    }

    /**
     * Handle order by & sort request
     */
    protected function handleOrdering(): void
    {
        if (request()->has("order_by") && request()->has("sort")) {
            session()->set($this->module_name . "_order_by", request()->order_by);
            session()->set($this->module_name . "_sort", request()->sort);
        }

        if (session()->has($this->module_name . "_order_by") && $this->module_name . "_sort") {
            $this->order_by = session()->get($this->module_name . "_order_by");
            $this->sort = session()->get($this->module_name . "_sort");
        }
    }

    /**
     * Handle pagination request
     */
    protected function handlePagination(): void
    {
        $page = $this->page;
        $limit = $this->limit;
        if (request()->has("page")) {
            session()->set(
                $this->module_name . "_page",
                intval(request()->page)
            );
        }
        if (request()->has("limit")) {
            session()->set(
                $this->module_name . "_limit",
                intval(request()->limit)
            );
            session()->set(
                $this->module_name . "_page",
                1
            );
        }

        $this->page = session()->get($this->module_name . "_page") ?? $page;
        $this->limit = session()->get($this->module_name . "_limit") ?? $limit;
    }

    /**
     * Handle search request
     */
    protected function handleSearch(): void
    {
        $where = [];
        if (request()->has("search") && trim(request()->search) != "") {
            $term = trim(request()->search);
            foreach ($this->search as $column) {
                // Search where column like search term
                $where[] = "($column LIKE '$term%')";
            }
            session()->set($this->module_name . "_term", $term);
            session()->set($this->module_name . "_search", [
                implode(" OR ", $where),
            ]);
            session()->set($this->module_name . "_page", 1);
        } elseif (request()->has("search") && trim(request()->search) == "") {
            session()->remove($this->module_name . "_term");
            session()->remove($this->module_name . "_search");
            session()->remove($this->module_name . "_page");
        }

        if (session()->has($this->module_name . "_search")) {
            $this->where[] = session()->get($this->module_name . "_search");
        }
    }

    /**
     * Return module not found response
     */
    public function moduleNotFound(): never
    {
        Flash::addFlash(
            "warning",
            "Oops! The requested record could not be found"
        );
        echo $this->indexPartial();
        exit();
    }

    /**
     * Return the module name
     */
    public function getModuleName(): string
    {
        return $this->module_name;
    }

    /**
     * Return the table name
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    /**
     * Return the template used for index
     */
    protected function getIndexTemplate(): string
    {
        return "backend/index.latte";
    }

    /**
     * Return the template used for custom index
     */
    protected function getCustomIndex(): string
    {
        return "backend/custom.latte";
    }

    /**
     * Return the template used for edit
     */
    protected function getEditTemplate(): string
    {
        return "backend/edit.latte";
    }

    /**
     * Return the template used for create
     */
    protected function getCreateTemplate(): string
    {
        return "backend/create.latte";
    }

    /**
     * Add a row to the audit table
     */
    protected function audit(
        string $user_id,
        string $table_name,
        string $table_id,
        string $field,
        string $value,
        string $message = ""
    ): void {
        $old = db()->select(
            "SELECT new_value
            FROM audit
            WHERE table_name = ? AND
            table_id = ? AND
            field = ?
            ORDER BY created_at DESC
            LIMIT 1",
            $table_name,
            $table_id,
            $field
        );
        if (db()->statement->rowCount() == 0 || $old->new_value != $value) {
            db()->run(
                "INSERT INTO audit SET
                user_id = ?,
                table_name = ?,
                table_id = ?,
                field = ?,
                old_value = ?,
                new_value = ?,
                message = ?,
                created_at = NOW()",
                [
                    $user_id,
                    $table_name,
                    $table_id,
                    $field,
                    $old->new_value ?? "NULL",
                    $value,
                    $message,
                ]
            );
        }
    }

    /**
     * Audit table columns
     * @param array<int,mixed> $columns
     */
    protected function auditColumns(array $columns, string $id, string $message = ""): void
    {
        foreach ($columns as $column => $value) {
            $this->audit(user()->id, $this->table_name, $id, $column, $value, $message);
        }
    }

    /**
     * Handle any database PDOException
     */
    protected function handleDatabaseException(PDOException $ex): void
    {
        if (config("app.debug")) {
            Flash::addFlash("database", $ex->getMessage());
        } else {
            logger(
                "error",
                $ex->getMessage(),
                sprintf("%s[%s]", $ex->getFile(), $ex->getLine())
            );
            Flash::addFlash("database", "Oops! A database error occurred");
        }
        // TODO this always returns to list view,
        // maybe we can stay in edit view
        echo $this->indexPartial();
        exit();
    }

    /**
     * Filter out columns that should not be used in the 
     * request for creation of / updating a record.
     */
    protected function getFilteredFormColumns(): array
    {
        $filtered_controls = ["upload", "image"];
        $data = request()->data();
        // Deal with "NULL" string
        array_walk(
            $data,
            fn (&$value, $key) => ($value = $value === "NULL" ? null : $value)
        );
        return array_filter(
            $data,
            fn ($value, $key) => $key != "csrf_token" &&
                !in_array($this->form_controls[$key], $filtered_controls),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Validate request based on user-supplied rules
     * @param array<int,mixed> $rules
     */
    protected function validate(array $rules): bool
    {
        $result = Validate::request($rules);
        $this->errors = Validate::$errors;
        return $result;
    }

    /**
     * Return the index QueryBuilder
     */
    protected function getIndexQuery(): ?QueryBuilder
    {
        if (is_null($this->table_name)) {
            return null;
        }
        $qb = QueryBuilder::select($this->table_name)->columns(
            array_keys($this->table_columns)
        );
        if (!empty($this->where)) {
            $qb->where(...$this->where);
        }
        if ($this->order_by == '') {
            $this->order_by = $this->key_col;
        }
        $qb->orderBy([$this->order_by => $this->sort]);
        if (!is_null($this->offset)) {
            $qb->limit($this->limit)->offset($this->offset);
        }
        return $qb;
    }

    /**
     * Return the edit QueryBuilder
     */
    protected function getEditQuery(string $id): QueryBuilder
    {
        $qb = QueryBuilder::select($this->table_name)
            ->columns(array_keys($this->form_columns))
            ->where([$this->key_col, $id]);

        return $qb;
    }

    /**
     * Override method for rendering custom content
     * Custom content is rendered if table_name is null
     */
    protected function customContent(): string
    {
        return '';
    }

    /**
     * Return the profiler array used in all views
     */
    protected function profiler(): array
    {
        global $global_start;
        $slow_traces = [];
        foreach (["Slow DB:" => db()->trace_counts] as $title => $traces) {
            //$slow_traces[] = $title;
            if ($traces) {
                uasort($traces, fn ($a, $b) => $b["time"] <=> $a["time"]);
                $i = 0;
                foreach ($traces as $key => $value) {
                    $i++;
                    if ($i > 10) {
                        break;
                    }
                    $pct =
                        number_format(
                            ($value["time"] / db()->total_time) * 100,
                            2
                        ) . "%";
                    $slow_traces[] = "{$key} &times; {$value["count"]}, {$value["time"]} <strong>{$pct}</strong>";
                }
            }
        }
        return [
            "show_profiler" => config("database.show_profiler"),
            "global_start" => $global_start,
            "total_php" => microtime(true) - $global_start,
            "db_total_time" => db()->total_time ?? 0,
            "db_num_queries" => db()->num_queries ?? 0,
            "slow_traces" => $slow_traces ?? [],
        ];
    }

    /**
     * Returns common data array used in all views
     */
    public function commonData(): array
    {
        $route = function (string $route_name, ?string $id = null) {
            return moduleRoute($route_name, $this->module_name, $id);
        };
        $moduleRoute = function (
            string $route_name,
            string $module_name,
            ?string $id = null
        ) {
            return moduleRoute($route_name, $module_name, $id);
        };
        $gravatar = fn (string $str) => md5(strtolower(trim($str)));
        $singular = function (string $str) {
            return substr($str, -1) === "s" ? rtrim($str, "s") : $str;
        };
        $request = fn (string $column) => request()->has($column)
            ? request()->$column
            : "";
        $session = fn (string $column) => session()->has($column)
            ? session()->get($column)
            : "";
        return [
            "profiler" => $this->profiler(),
            "has_flash" => Flash::hasFlash(),
            "request" => $request,
            "session" => $session,
            "gravatar" => $gravatar,
            "route" => $route,
            "moduleRoute" => $moduleRoute,
            "route_name" => request()->route->getName(),
            "module_name" => $this->module_name,
            "module_title" => $this->module_title,
            "module_title_singular" => $singular($this->module_title),
            "key_col" => $this->key_col,
            "name_col" => $this->name_col,
        ];
    }

    /**
     * Return a form control closure used edit / create views
     * There are a couple of different ways to render a form control:
     * 1. Define a function callback that will render a control manually
     * 2. Use a pre-defined control type, which will render a control automatically
     */
    protected function formControls(?string $id = null): \Closure
    {
        $controls = function ($name, $value, ...$args) use ($id) {
            $fc = new FormControls($id);
            if (!isset($this->form_controls[$name])) {
                return $fc->plain($name, $value);
            }
            if (is_callable($this->form_controls[$name])) {
                return $this->form_controls[$name]($name, $value, ...$args);
            }
            return match ($this->form_controls[$name]) {
                "input" => $fc->input($name, $value, "text"),
                "textarea" => $fc->textarea($name, $value),
                "disabled" => $fc->input(
                    $name,
                    $value,
                    "text",
                    attrs: "disabled=true"
                ),
                "readonly" => $fc->input(
                    $name,
                    $value,
                    "text",
                    attrs: "readonly"
                ),
                "plain" => $fc->plain($name, $value),
                "select" => $fc->select(
                    $name,
                    $value,
                    isset($this->select_options[$name])
                        ? $this->select_options[$name]
                        : []
                ),
                "nselect" => $fc->nselect(
                    $name,
                    $value,
                    isset($this->select_options[$name])
                        ? $this->select_options[$name]
                        : []
                ),
                "number" => $fc->input($name, $value, "number"),
                "color" => $fc->input($name, $value, "color"),
                "upload" => $fc->file(
                    $name,
                    $value,
                    sprintf(
                        'accept="%s"',
                        implode(", ", $this->file_extensions)
                    )
                ),
                "image" => $fc->image(
                    $name,
                    $value,
                    sprintf(
                        'accept="%s"',
                        implode(", ", $this->image_extensions)
                    )
                ),
                "checkbox" => $fc->checkbox($name, $value ?? 0),
                "switch" => $fc->switch($name, $value ?? 0),
                default => $fc->plain($name, $value),
            };
        };
        return $controls;
    }

    /**
     * Get the total number of results from the index query
     */
    protected function getTotalResults(): int
    {
        if (empty($this->table_columns)) return 0;
        $qb = $this->getIndexQuery();
        $stmt = db()->run($qb->build(), $qb->values());
        return $stmt?->rowCount() ?? 0;
    }

    /**
     * Returns an array of data used for the table view
     */
    protected function tableData(): array|bool
    {
        if (is_null($this->table_name) || empty($this->table_columns)) {
            return false;
        }
        $data = [];
        $this->total_results = $this->getTotalResults();
        $this->total_pages = ceil($this->total_results / $this->limit);
        if ($this->page > $this->total_pages) {
            $this->page = $this->total_pages;
        }
        if ($this->page < 1) {
            $this->page = 1;
        }
        $this->offset = ($this->page - 1) * $this->limit;
        $qb = $this->getIndexQuery();
        $data = db()
            ->run($qb->build(), $qb->values())
            ->fetchAll();
        return $data;
    }

    /**
     * If the subquery has an alias, then we want return an updated
     * table columns where we only use the alias name as the table
     * columns key.
     * @param array<int,mixed> $columns
     */
    protected function tableAlias(array $columns): array
    {
        $filtered = array_map(function ($column) {
            $lower = strtolower($column);
            if (preg_match('/( as )/', $lower)) {
                $split = explode(' as ', $lower);
                return end($split);
            }
            return $column;
        }, array_keys($columns));
        $filtered_table_columns = [];
        $idx = 0;
        foreach ($columns as $_ => $value) {
            $filtered_table_columns[$filtered[$idx]] = $value;
            $idx++;
        }
        return $filtered_table_columns;
    }

    /**
     * Return data used for index view
     * @return array<string,mixed>
     */
    protected function getIndexData(): array
    {
        $this->processTableRequest();
        try {
            $data = $this->tableData();
        } catch (PDOException $ex) {
            $this->handleDatabaseException($ex);
        }

        return [
            ...$this->commonData(),
            "custom_content" => $this->customContent(),
            "has_search" => !empty($this->search),
            "has_filter_links" => !empty($this->filter_links),
            "filter_links" => $this->filter_links,
            "filter_link" => $this->filter_link,
            "table" => [
                "create" => $this->table_create,
                "edit" => $this->table_edit,
                "destroy" => $this->table_destroy,
                "order_by" => $this->order_by,
                "sort" => $this->sort,
                "total_results" => $this->total_results,
                "total_pages" => $this->total_pages,
                "per_page" => $this->limit,
                "per_page_options" => [5, 15, 25, 50, 100, 200, 500, 1000],
                "page" => $this->page,
                "data" => $data,
                "columns" => $this->tableAlias($this->table_columns),
                "col_span" => count($this->table_columns) + 1,
            ],
        ];
    }

    /**
     * Return data used for create view
     * @return array<string,mixed>
     */
    protected function getCreateData(): array
    {
        $this->processFormRequest();
        $fc = $this->formControls();
        return [
            ...$this->commonData(),
            "controls" => $fc,
            "form" => [
                "data" => [],
                "columns" => $this->form_columns,
            ],
        ];
    }

    /**
     * Return data used for edit view
     * @return array<string,mixed>
     */
    protected function getEditData(string $id): array
    {
        $this->processFormRequest($id);
        $qb = $this->getEditQuery($id);
        $data = null;
        try {
            $data = !is_null($qb)
                ? db()
                ->run($qb->build(), $qb->values())
                ->fetch()
                : [];
        } catch (PDOException $ex) {
            $this->handleDatabaseException($ex);
        }
        if (!$data) {
            $this->moduleNotFound();
        }
        $fc = $this->formControls($id);

        return [
            ...$this->commonData(),
            "id" => $id,
            "controls" => $fc,
            "form" => [
                "data" => $data,
                "columns" => $this->form_columns,
            ],
        ];
    }

    /**-------- ENDPOINTS -----------------------------------------------*/

    public function index(): string
    {
        $template = !is_null($this->table_name)
            ? $this->getIndexTemplate()
            : $this->getCustomIndex();
        return latte($template, $this->getIndexData());
    }

    public function indexPartial(): string
    {
        $template = !is_null($this->table_name)
            ? $this->getIndexTemplate()
            : $this->getCustomIndex();
        return latte($template, $this->getIndexData(), "content");
    }

    public function edit(string $id): string
    {
        return latte($this->getEditTemplate(), $this->getEditData($id));
    }

    public function editPartial(string $id): string
    {
        return latte(
            $this->getEditTemplate(),
            $this->getEditData($id),
            "content"
        );
    }

    public function create(): string
    {
        return latte($this->getCreateTemplate(), $this->getCreateData());
    }

    public function createPartial(): string
    {
        return latte(
            $this->getCreateTemplate(),
            $this->getCreateData(),
            "content"
        );
    }

    public function store(): string
    {
        if ($this->validate($this->validation) && $this->table_create) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::insert($this->table_name)->columns($columns);
            try {
                $result = (bool) db()->run($qb->build(), $qb->values());
                $id = db()->lastInsertId();
                if ($id && request()->files()) {
                    $result &= $this->handleUpload($id);
                }
                if ($result) {
                    $this->auditColumns($columns, $id, 'INSERT');
                    Flash::addFlash("success", "Record created successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while creating new record"
                    );
                }
            } catch (PDOException $ex) {
                $this->handleDatabaseException($ex);
            }
        }
        return $this->createPartial();
    }

    public function update(string $id): string
    {
        if ($this->validate($this->validation) && $this->table_edit) {
            $columns = $this->getFilteredFormColumns();
            $qb = QueryBuilder::update($this->table_name)
                ->columns($columns)
                ->where(["id", $id]);
            try {
                $result = (bool) db()->run($qb->build(), $qb->values());
                if ($result && request()->files()) {
                    $result &= $this->handleUpload($id);
                }
                if ($result) {
                    $this->auditColumns($columns, $id, 'UPDATE');
                    Flash::addFlash("success", "Record updated successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while updating record"
                    );
                }
            } catch (PDOException $ex) {
                $this->handleDatabaseException($ex);
            }
        }
        return $this->editPartial($id);
    }

    public function destroy(string $id): string
    {
        if ($this->table_destroy) {
            $qb = QueryBuilder::delete($this->table_name)->where([$this->key_col, $id]);
            try {
                $result = db()->run($qb->build(), $qb->values());
                if ($result) {
                    $this->auditColumns([$this->key_col => "NULL"], $id, 'DELETE');
                    Flash::addFlash("success", "Record deleted successfully");
                } else {
                    Flash::addFlash(
                        "danger",
                        "Oops! An unknown issue occurred while deleting record"
                    );
                }
            } catch (PDOException $ex) {
                $this->handleDatabaseException($ex);
            }
        }
        return $this->indexPartial();
    }
}
