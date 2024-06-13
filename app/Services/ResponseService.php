<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResponseService
{
    private $data;
    private $message;
    private $success;

    // private $responseCode;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function success($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'success' : $message;

        // set the message
        $this->setMessage($message);

        // set response code
        $this->setResponseCode($responseCode);

        $this->success = true;

        return (object) $this->responseWrapper();
    }

    public function error($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'error' : $message;

        // set the message
        $this->setMessage($message);

        // set response code
        $this->setResponseCode($responseCode);

        $this->success = false;

        return (object) $this->responseWrapper();
    }

    private function paginateCollection($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    private function filterData($items, $filters)
    {
        // Apply search
        if (!empty($filters['search_columns']) && !empty($filters['search_key'])) {
            $searchColumns = explode(',', $filters['search_columns']);
            $searchKey = $filters['search_key'];

            $items = $items->filter(function ($item) use ($searchColumns, $searchKey) {
                foreach ($searchColumns as $searchColumn) {
                    if (Str::contains($searchColumn, '.')) {
                        // Handle nested keys if necessary
                        $nestedKeys = explode('.', $searchColumn);
                        $value = $item;
                        foreach ($nestedKeys as $key) {
                            if (is_array($value) && isset($value[$key])) {
                                $value = $value[$key];
                            } elseif (is_object($value) && isset($value->$key)) {
                                $value = $value->$key;
                            } else {
                                $value = null;
                                break;
                            }
                        }
                        if ($value && Str::contains(strtolower($value), strtolower($searchKey))) {
                            return true;
                        }
                    } else {
                        if ((is_array($item) && isset($item[$searchColumn]) && Str::contains(strtolower($item[$searchColumn]), strtolower($searchKey))) ||
                            (is_object($item) && isset($item->$searchColumn) && Str::contains(strtolower($item->$searchColumn), strtolower($searchKey)))
                        ) {
                            return true;
                        }
                    }
                }
                return false;
            })->values(); // Menghapus kunci indeks dan mengatur ulang indeks array
        }


        // Apply filters
        if (!empty($filters['filter_columns']) && !empty($filters['filter_keys'])) {
            $filterColumns = explode(',', $filters['filter_columns']);
            $filterKeys = explode(',', $filters['filter_keys']);

            foreach ($filterColumns as $index => $column) {
                if (isset($filterKeys[$index])) {
                    $items = $items->filter(function ($item) use ($column, $filterKeys, $index) {
                        if (is_array($item) && isset($item[$column])) {
                            return strpos($item[$column], $filterKeys[$index]) !== false;
                        } elseif (is_object($item) && isset($item->$column)) {
                            return strpos($item->$column, $filterKeys[$index]) !== false;
                        }
                        return false;
                    });
                }
            }
        }

        // Apply sorting
        if (!empty($filters['sort_column']) && !empty($filters['sort_type'])) {
            $sortColumn = $filters['sort_column'];
            $sortType = strtolower($filters['sort_type']) == 'desc' ? 'desc' : 'asc';

            $items = $items->sortBy(function ($item) use ($sortColumn) {
                if (is_array($item) && isset($item[$sortColumn])) {
                    return $item[$sortColumn];
                } elseif (is_object($item) && isset($item->$sortColumn)) {
                    return $item->$sortColumn;
                }
                return null;
            }, SORT_REGULAR, $sortType == 'desc');
        }

        return $items;
    }

    private function responseWrapper()
    {
        // handle empty data
        $data = (empty($this->data)) ? null : $this->data;

        $response = [
            'message' => $this->message,
        ];

        // Get filters from request
        $filters = request()->only([
            'search_columns', 'search_key',
            'filter_columns', 'filter_keys',
            'sort_column', 'sort_type',
            'entries', 'page'
        ]);

        $perPage = $filters['entries'] ?? 15;
        $page = $filters['page'] ?? 1;

        if ($data instanceof LengthAwarePaginator) {
            $data = $data->toArray();
            $response['data'] = $data['data'];
            $response['meta'] = $this->getPaginationMeta($data);
        } elseif ($data instanceof Collection || is_array($data)) {
            // Apply filter and sort
            $data = $this->filterData(Collection::make($data), $filters);

            // Paginate the filtered and sorted data
            $paginatedData = $this->paginateCollection($data, $perPage, $page);
            $data = $paginatedData->toArray();
            $response['data'] = $data['data'];
            $response['meta'] = $this->getPaginationMeta($data, true);
        } else {
            $response['data'] = $data;
        }

        return $response;
    }

    private function getPaginationMeta($data, $fromRedis = false)
    {
        $meta = [
            'current_page' => $data['current_page'],
            'from' => $data['from'],
            'last_page' => $data['last_page'],
            'next_page_url' => $data['next_page_url'],
            'path' => $data['path'],
            'per_page' => $data['per_page'],
            'prev_page_url' => $data['prev_page_url'],
            'to' => $data['to'],
            'total' => $data['total'],
        ];

        // Adjust URLs if data is from Redis
        if ($fromRedis) {
            $meta['next_page_url'] = $this->adjustUrl($meta['next_page_url']);
            $meta['prev_page_url'] = $this->adjustUrl($meta['prev_page_url']);
            $meta['path'] = request()->url();
        }

        return $meta;
    }

    private function adjustUrl($url)
    {
        if ($url) {
            return request()->url() . '?' . parse_url($url, PHP_URL_QUERY);
        }
        return null;
    }

    private function setMessage($message)
    {
        // check if message constructed in array format (multiple message)
        if (is_array($message)) {
            $extract = array_values($message);
            $this->message = $extract[0];
        } else {
            $this->message = $message;
        }
    }

    private function setResponseCode($responseCode)
    {
        if (!empty($responseCode) && is_numeric($responseCode))
            http_response_code($responseCode);
    }
}
