<?php

namespace Omnistay;

use Omnistay\Model\Page;
use Omnistay\Model\SearchResult;
use Omnistay\Model\StayQuery;

interface SearchInterface
{
    /**
     * Properties around the place asked, with what the stay costs there
     * when the query has dates; best first as the provider ranks them. A
     * page's token asks for the next ones.
     *
     * @return Page<SearchResult>
     */
    public function search(StayQuery $query): Page;
}
