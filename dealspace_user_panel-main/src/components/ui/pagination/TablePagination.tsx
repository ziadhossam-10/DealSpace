import { generatePageNumbers } from "../../../utils/helpers"

type EmptyTableProps = {
    page: number;
    totalPages: number;
    totalCount: number;
    setPage: (page: number) => void;
    pageSize: number;
    setPageSize: (pageSize: number) => void;
};

export const TablePagination: React.FC<EmptyTableProps> = ({ page, totalPages, totalCount, setPage, pageSize, setPageSize }) => {
    return (
        <div className="flex justify-between mt-4 w-full">
            <select 
                value={pageSize} 
                onChange={(e) => setPageSize(Number(e.target.value))} 
                className="w-[180px] max-w-[180px] bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            >
                <option value="" disabled>Items per page</option>
                <option value="5">5 items per page</option>
                <option value="10">10 items per page</option>
                <option value="20">20 items per page</option>
                <option value="50">50 items per page</option>
            </select>
            <h1 className="text-lg mb-0 flex items-center">
                {pageSize < totalCount ? pageSize : totalCount} of {totalCount}
            </h1>
            <nav aria-label="Page navigation">
                <ul className="inline-flex -space-x-px text-base h-full">
                    <li>
                        <button 
                            disabled={page === 1} 
                            onClick={() => setPage(page - 1)} 
                            className="px-4 h-10 border rounded-s-lg hover:bg-gray-100 h-full"
                        >
                            Previous
                        </button>
                    </li>
                    {generatePageNumbers(page, totalPages).map((p) => (
                        <li key={p}>
                            <button 
                                onClick={() => setPage(p)} 
                                className={`px-4 h-10 h-full border ${p === page ? "bg-gray-300" : "hover:bg-gray-100"}`}
                            >
                                {p}
                            </button>
                        </li>
                    ))}
                    <li>
                        <button 
                            disabled={page >= totalPages} 
                            onClick={() => setPage(page + 1)} 
                            className="px-4 h-10 border rounded-e-lg hover:bg-gray-100 h-full"
                        >
                            Next
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    )
}