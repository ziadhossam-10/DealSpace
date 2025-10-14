"use client"
import { useCallback, useEffect, useState, useRef, useMemo } from "react"
import { toast } from "react-toastify"
import { useNavigate, useSearchParams } from "react-router"

// Internal Components
import { TablePagination } from "../../components/ui/pagination/TablePagination"

// CRUDS Components
import DeleteModal from "../../components/modal/DeleteModal"
import BulkDeleteModal from "../../components/modal/BulkDeleteModal"
import CampaignModal from "./components/CampaignModal"

// API Calls
import {
  useGetPeopleQuery,
  useDeletePersonMutation,
  useBulkDeletePeopleMutation,
  useLazyDownloadTemplateQuery,
  useImportUsersMutation,
  useBulkExportPeopleMutation,
  useGetStagesQuery as useGetPeopleStagesQuery,
  useSendEmailCampaignMutation,
} from "./peopleApi"
import { useGetTeamsQuery } from "../teams/teamsApi"
import { useGetUsersQuery } from "../users/usersApi"
import { useGetDealTypesQuery } from "../deals/dealsApi"
import type { Person } from "../../types/people"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"
import { FileDown, FileUp, UserPlus, Search, X, Filter, Check } from "lucide-react"
import { useGetEmailAccountsQuery } from "../manageEmails/emailAccountsApi"

interface PeopleFilters {
  search: string
  stage_id: number | null
  team_id: number | null
  user_ids: number[]
  deal_type_id: number | null
}

interface CampaignData {
  name: string
  description: string
  subject: string
  body: string
  body_html: string
  email_account_id: number | null
  use_all_emails: boolean
  recipient_ids?: number[]
  is_all_selected: boolean
  // Filter options
  stage_id?: number | null
  team_id?: number | null
  user_ids?: number[]
  search?: string
  deal_type_id?: number | null
}

export default function People() {
  const router = useNavigate()

  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedPersonId, setSelectedPersonId] = useState<number | null>(null)
  const [isShowImportModal, setShowImportModal] = useState(false)

  // Filter State
  const filters = useRef<PeopleFilters>({
    search: "",
    stage_id: null,
    team_id: null,
    user_ids: [],
    deal_type_id: null,
  })

  const [searchParams] = useSearchParams()

  // Parse filters from URL parameters
  const urlFilters: PeopleFilters = useMemo(() => {
    const urlFilters: PeopleFilters = {
      search: "",
      stage_id: null,
      team_id: null,
      user_ids: [],
      deal_type_id: null,
    }

    const search = searchParams.get("search")
    if (search) urlFilters.search = search

    const stageId = searchParams.get("stage_id")
    if (stageId) urlFilters.stage_id = Number(stageId)

    const teamId = searchParams.get("team_id")
    if (teamId) urlFilters.team_id = Number(teamId)

    const dealTypeId = searchParams.get("deal_type_id")
    if (dealTypeId) urlFilters.deal_type_id = Number(dealTypeId)

    const userIds = searchParams.get("user_ids")
    if (userIds) urlFilters.user_ids = userIds.split(",").map(Number)

    return urlFilters
  }, [searchParams])

  filters.current = urlFilters


  // Filter Modal State
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false)
  const [tempFilters, setTempFilters] = useState<PeopleFilters>(filters.current)

  // Campaign Modal State
  const [isCampaignModalOpen, setIsCampaignModalOpen] = useState(false)

  // Search states for dropdowns
  const [teamSearch, setTeamSearch] = useState("")
  const [userSearch, setUserSearch] = useState("")

  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedIds, setDeselectedIds] = useState<number[]>([])

  // Bulk delete modal
  const [isShowBulkDeleteModal, setShowBulkDeleteModal] = useState(false)

  // API Queries
  const {
    data,
    isLoading,
    error,
    refetch: fetchPeople,
  } = useGetPeopleQuery({
    page,
    per_page: pageSize,
    ...filters.current,
    user_ids: filters.current.user_ids.length > 0 ? filters.current.user_ids : undefined,
  })

  // Filter data queries
  const { data: stagesData } = useGetPeopleStagesQuery()
  const { data: teamsData } = useGetTeamsQuery({ page: 1, per_page: 100, search: teamSearch })
  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 100, search: userSearch })
  const { data: dealTypesData } = useGetDealTypesQuery()
  const { data: emailAccountsData } = useGetEmailAccountsQuery({
    per_page: 100,
    page: 1,
  })

  // API Mutations
  const [deletePerson, { isLoading: isLoadingDelete }] = useDeletePersonMutation()
  const [bulkDeletePeople, { isLoading: isLoadingBulkDelete }] = useBulkDeletePeopleMutation()
  const [bulkExportPeople] = useBulkExportPeopleMutation()
  const [triggerDownloadTemplate] = useLazyDownloadTemplateQuery()
  const [importUsers] = useImportUsersMutation()
  const [sendEmailCampaign, { isLoading: isLoadingSendCampaign }] = useSendEmailCampaignMutation()

  // Helper function to serialize filters for URL
  const serializeFilters = (): string => {
    
    const params = new URLSearchParams()

    if (filters.current.search) params.set("search", filters.current.search)
    if (filters.current.stage_id) params.set("stage_id", filters.current.stage_id.toString())
    if (filters.current.team_id) params.set("team_id", filters.current.team_id.toString())
    if (filters.current.deal_type_id) params.set("deal_type_id", filters.current.deal_type_id.toString())
    if (filters.current.user_ids.length > 0) {
      params.set("user_ids", filters.current.user_ids.join(","))
    }

    return params.toString()
  }

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: Person) => <div>{row.name}</div>,
      isMain: true,
    },
    {
      key: "email",
      label: "Email",
      render: (row: Person) => <div>{row.emails?.[0]?.value || "-"}</div>,
      isMain: true,
    },
    {
      key: "phone",
      label: "Phone",
      render: (row: Person) => <div>{row.phones?.[0]?.value || "-"}</div>,
      isMain: true,
    },
    {
      key: "stage",
      label: "Stage",
      render: (row: Person) => <div>{row.stage?.name}</div>,
      isMain: false,
    },
    {
      key: "assigned_to",
      label: "Assigned To",
      render: (row: Person) => <div>{row.assigned_user?.name}</div>,
      isMain: false,
    },
    {
      key: "price",
      label: "Price",
      render: (row: Person) => <div>{row.price ? '$' + Number.parseFloat(row.price).toLocaleString() : "N/A"}</div>,
      isMain: true,
    },
    {
      key: "tags",
      label: "Tags",
      render: (row: Person) => (
        <div className="flex flex-wrap gap-1">
          {row.tags?.map((tag) => (
            <span
              key={tag.id}
              className="px-2 py-1 text-xs rounded-full text-white"
              style={{ backgroundColor: tag.color }}
            >
              {tag.name}
            </span>
          ))}
        </div>
      ),
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: Person) => (
        <div className="flex items-center gap-2">
          <button className="px-3 py-1 bg-green-500 text-white rounded" onClick={() => handleEdit(row.id)}>
            Show
          </button>
          <button className="px-3 py-1 bg-red-500 text-white rounded" onClick={() => handleDelete(row.id)}>
            Delete
          </button>
        </div>
      ),
      isMain: true,
    },
  ]

  // Filter handlers
  const handleTempFilterChange = (key: keyof PeopleFilters, value: any) => {
    setTempFilters((prev) => ({
      ...prev,
      [key]: value,
    }))
  }

  const handleUserSelect = (userId: number) => {
    setTempFilters((prev) => ({
      ...prev,
      user_ids: prev.user_ids.includes(userId)
        ? prev.user_ids.filter((id) => id !== userId)
        : [...prev.user_ids, userId],
    }))
  }

  const removeUserFilter = (userId: number) => {
    setTempFilters((prev) => ({
      ...prev,
      user_ids: prev.user_ids.filter((id) => id !== userId),
    }))
  }

  const applyFilters = () => {
    filters.current = tempFilters
    setPage(1)
    setIsFilterModalOpen(false)
    const filterParams = serializeFilters()
    const url = filterParams ? `/people?${filterParams}` : `/people`
    router(url)
  }

  const clearAllFilters = () => {
    const emptyFilters = {
      search: "",
      stage_id: null,
      team_id: null,
      user_ids: [],
      deal_type_id: null,
    }
    setTempFilters(emptyFilters)
    filters.current = emptyFilters 
    setPage(1)
    router('/people')
    setIsFilterModalOpen(false)
  }

  const hasActiveFilters = () => {
    return filters.current.search || filters.current.stage_id || filters.current.team_id || filters.current.user_ids.length > 0 || filters.current.deal_type_id
  }

  const openFilterModal = () => {
    setTempFilters(filters.current) // Initialize temp filters with current filters
    setIsFilterModalOpen(true)
  }

  const closeFilterModal = () => {
    setTempFilters(filters.current) // Reset temp filters to current filters
    setIsFilterModalOpen(false)
    setTeamSearch("")
    setUserSearch("")
  }

  // Campaign handlers
  const openCampaignModal = () => {
    if (selectedIds.length === 0 && !allSelected) {
      toast.error("Please select people to send email campaign")
      return
    }
    setIsCampaignModalOpen(true)
  }

  const closeCampaignModal = () => {
    setIsCampaignModalOpen(false)
  }

  const handleCampaignSubmit = async (
    campaignData: Omit<
      CampaignData,
      "recipient_ids" | "is_all_selected" | "stage_id" | "team_id" | "user_ids" | "search" | "deal_type_id"
    >,
  ) => {
    try {
      // Ensure email_account_id is not null
      if (!campaignData.email_account_id) {
        toast.error("Please select an email account")
        return
      }

      const payload: CampaignData = {
        ...campaignData,
        recipient_ids: allSelected ? undefined : selectedIds,
        is_all_selected: allSelected,
        // Include current filters if using all selected
        ...(allSelected &&
          hasActiveFilters() && {
            stage_id: filters.current.stage_id,
            team_id: filters.current.team_id,
            user_ids: filters.current.user_ids,
            search: filters.current.search,
            deal_type_id: filters.current.deal_type_id,
          }),
      }

      if (payload.email_account_id === null) {
        throw new Error("email_account_id must be a number")
      }

      if (payload.email_account_id === null) {
        throw new Error("email_account_id must be a number")
      }

      const response = await sendEmailCampaign({ ...payload, email_account_id: payload.email_account_id }).unwrap()
      toast.success(response.message || "Email campaign sent successfully")
      setIsCampaignModalOpen(false)
      setSelectedIds([])
      setAllSelected(false)
      setDeselectedIds([])
    } catch (error: any) {
      toast.error(error?.data?.message || "Failed to send campaign")
    }
  }

  // Filter teams based on search
  const filteredTeams =
    teamsData?.data?.items?.filter((team) => team.name.toLowerCase().includes(teamSearch.toLowerCase())) || []

  // Filter users based on search
  const filteredUsers =
    usersData?.data?.items?.filter((user) => user.name.toLowerCase().includes(userSearch.toLowerCase())) || []

  // Bulk actions with filters
  const bulkActions = [
    {
      label: "Delete Selected",
      action: (ids: number[]) => {
        if (ids.length > 0) {
          setShowBulkDeleteModal(true)
        }
      },
    },
    {
      label: "Export Selected",
      action: async (ids: number[]) => {
        try {
          toast.info(`Exporting ${allSelected ? totalCount - deselectedIds.length : ids.length} people...`)
          const blob = await bulkExportPeople({
            isAllSelected: allSelected,
            ids: selectedIds,
            exceptionIds: deselectedIds,
            // Include current filters in export
            filters: hasActiveFilters() ? filters.current : undefined,
          }).unwrap()

          // Create download link
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement("a")
          link.href = url
          link.setAttribute("download", `people-export-${new Date().toISOString().split("T")[0]}.xlsx`)
          document.body.appendChild(link)
          link.click()
          link.parentNode?.removeChild(link)
          window.URL.revokeObjectURL(url)

          setAllSelected(false)
          setSelectedIds([])
          setDeselectedIds([])
          toast.success("Export completed successfully")
        } catch (error: any) {
          toast.error("Failed to export data")
          console.error("Export error:", error)
        }
      },
    },
    {
      label: "Send Email Campaign",
      action: (ids: number[]) => {
        openCampaignModal()
      },
    },
  ]

  // useEffect Hooks
  useEffect(() => {
    fetchPeople()
  }, [page, pageSize, filters])

  // Update useEffect to set pagination data from the query response
  useEffect(() => {
    if (data?.data) {
      if (data.data.meta.last_page) {
        setTotalPages(data.data.meta.last_page)
      }
      if (data.data.meta.total) {
        setTotalCount(data.data.meta.total)
      }
    }
  }, [data])

  const handleDownloadTemplate = async () => {
    try {
      const blob = await triggerDownloadTemplate().unwrap()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement("a")
      link.href = url
      link.setAttribute("download", "people-template.xlsx")
      document.body.appendChild(link)
      link.click()
      link.parentNode?.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error: any) {
      toast.error("Failed to download template")
      console.error("Download error:", error)
    }
  }

  // Handle selection change
  const handleSelectionChange = (selected: number[], isAllSelected: boolean, deselected: number[]) => {
    setSelectedIds(selected)
    setAllSelected(isAllSelected)
    setDeselectedIds(deselected)
  }

  // Navigate to Add Person page
  const handleAdd = useCallback(() => {
    router("/people/add")
  }, [router])

  // Navigate to Edit Person page with filters
  const handleEdit = useCallback((id: number) => {
    const filterParams = serializeFilters()
    const url = filterParams ? `/people/${id}?${filterParams}` : `/people/${id}`
    router(url)
  }, [router])


  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedPersonId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedPersonId) {
      try {
        const response = await deletePerson(selectedPersonId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Person deleted successfully")
          handleCancelDelete()
          fetchPeople()
        } else {
          toast.error(response.message || "Error deleting person")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the person")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedPersonId(null)
  }, [])

  // Bulk Delete
  const handleConfirmBulkDelete = async () => {
    try {
      const response = await bulkDeletePeople({
        isAllSelected: allSelected,
        ids: selectedIds,
        exceptionIds: deselectedIds,
        // Include current filters in bulk delete
        filters: hasActiveFilters() ? filters.current : undefined,
      }).unwrap()

      if (response && response.status) {
        toast.success(response.message || `People deleted successfully`)
        setShowBulkDeleteModal(false)
        setSelectedIds([])
        setAllSelected(false)
        setDeselectedIds([])
        fetchPeople()
      } else {
        toast.error(response.message || "Error deleting people")
      }
    } catch (error) {
      console.log(error)
    }
  }

  // Cancel Bulk Delete
  const handleCancelBulkDelete = useCallback(() => {
    setShowBulkDeleteModal(false)
  }, [])

  return (
    <div className="container mx-auto">
      <div className="p-4">
        {/* Active Filters Display */}
        {hasActiveFilters() && (
          <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-blue-800">Active Filters:</span>
                <div className="flex flex-wrap gap-2">
                  {filters.current.search && (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      Search: "{filters.current.search}"
                    </span>
                  )}
                  {filters.current.stage_id && (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      Stage: {stagesData?.data?.find((s) => s.id === filters.current.stage_id)?.name}
                    </span>
                  )}
                  {filters.current.team_id && (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      Team: {teamsData?.data?.items?.find((t) => t.id === filters.current.team_id)?.name}
                    </span>
                  )}
                  {filters.current.deal_type_id && (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      Deal Type: {dealTypesData?.data?.find((d) => d.id === filters.current.deal_type_id)?.name}
                    </span>
                  )}
                  {filters.current.user_ids.length > 0 && (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                      Users: {filters.current.user_ids.length} selected
                    </span>
                  )}
                </div>
              </div>
              <button onClick={clearAllFilters} className="text-sm text-blue-600">Clear x</button>
            </div>
          </div>
        )}

        <DynamicTable
          pageTitle="People"
          actionButton={
            <div className="flex items-center gap-3">
              {/* Filter Button */}
              <button
                className={`relative group p-2 rounded ${
                  hasActiveFilters() ? "bg-blue-600 text-white" : "bg-gray-200 text-gray-700"
                }`}
                onClick={openFilterModal}
              >
                <Filter size={18} />
                {hasActiveFilters() && (
                  <span className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                )}
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Filters
                </div>
              </button>

              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
                <UserPlus size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Add Person
                </div>
              </button>

              <button className="relative group bg-blue-600 text-white p-2 rounded" onClick={handleDownloadTemplate}>
                <FileDown size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Download Template
                </div>
              </button>

              <button
                className="relative group bg-green-600 text-white p-2 rounded"
                onClick={() => setShowImportModal(true)}
              >
                <FileUp size={18} />
                <div className="absolute top-full right-0 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Import Excel
                </div>
              </button>
            </div>
          }
          columns={columns}
          data={data?.data?.items || []}
          totalCount={totalCount}
          currentPage={page}
          onSelectionChange={handleSelectionChange}
          bulkActions={bulkActions}
          idField="id"
          noDataTilte="No people Found"
          noDataDescription="No people found upload or insert new people"
          error={error}
          isLoading={isLoading}
        />

        {(data?.data?.items?.length || 0) > 0 && (
          <TablePagination
            page={page}
            totalPages={totalPages}
            totalCount={totalCount}
            setPage={setPage}
            pageSize={pageSize}
            setPageSize={setPageSize}
          />
        )}

        {/* Filter Modal */}
        {isFilterModalOpen && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-xl font-semibold text-gray-900">Filter People</h2>
                  <button onClick={closeFilterModal} className="text-gray-400 hover:text-gray-600 transition-colors">
                    <X size={24} />
                  </button>
                </div>

                <div className="space-y-6">
                  {/* Search Filter */}
                  <div>
                    <label className="block text-sm font-medium mb-2 text-gray-700">Search</label>
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                      <input
                        type="text"
                        placeholder="Search people..."
                        value={tempFilters.search}
                        onChange={(e) => handleTempFilterChange("search", e.target.value)}
                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Stage Filter */}
                    <div>
                      <label className="block text-sm font-medium mb-2 text-gray-700">Stage</label>
                      <select
                        value={tempFilters.stage_id?.toString() || ""}
                        onChange={(e) =>
                          handleTempFilterChange(
                            "stage_id",
                            e.target.value === "" ? null : Number.parseInt(e.target.value),
                          )
                        }
                        className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                      >
                        <option value="">All Stages</option>
                        {stagesData?.data?.map((stage) => (
                          <option key={stage.id} value={stage.id.toString()}>
                            {stage.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Deal Type Filter */}
                    <div>
                      <label className="block text-sm font-medium mb-2 text-gray-700">Deal Type</label>
                      <select
                        value={tempFilters.deal_type_id?.toString() || ""}
                        onChange={(e) =>
                          handleTempFilterChange(
                            "deal_type_id",
                            e.target.value === "" ? null : Number.parseInt(e.target.value),
                          )
                        }
                        className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                      >
                        <option value="">All Types</option>
                        {dealTypesData?.data?.map((type) => (
                          <option key={type.id} value={type.id.toString()}>
                            {type.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  {/* Team Filter with Search */}
                  <div>
                    <label className="block text-sm font-medium mb-2 text-gray-700">Team</label>
                    <div className="space-y-2">
                      <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                          type="text"
                          placeholder="Search teams..."
                          value={teamSearch}
                          onChange={(e) => setTeamSearch(e.target.value)}
                          className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                      </div>
                      <select
                        value={tempFilters.team_id?.toString() || ""}
                        onChange={(e) =>
                          handleTempFilterChange(
                            "team_id",
                            e.target.value === "" ? null : Number.parseInt(e.target.value),
                          )
                        }
                        className="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                      >
                        <option value="">All Teams</option>
                        {filteredTeams.map((team) => (
                          <option key={team.id} value={team.id.toString()}>
                            {team.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  {/* Users Filter with Search */}
                  <div>
                    <label className="block text-sm font-medium mb-2 text-gray-700">Assigned Users</label>
                    <div className="space-y-3">
                      <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                          type="text"
                          placeholder="Search users..."
                          value={userSearch}
                          onChange={(e) => setUserSearch(e.target.value)}
                          className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                      </div>

                      {/* Users List */}
                      <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-lg">
                        {filteredUsers.map((user) => (
                          <div
                            key={user.id}
                            className="flex items-center justify-between p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                          >
                            <span className="text-sm text-gray-700">{user.name}</span>
                            <button
                              onClick={() => handleUserSelect(user.id)}
                              className={`w-5 h-5 rounded border-2 flex items-center justify-center ${
                                tempFilters.user_ids.includes(user.id)
                                  ? "bg-blue-600 border-blue-600 text-white"
                                  : "border-gray-300 hover:border-blue-400"
                              }`}
                            >
                              {tempFilters.user_ids.includes(user.id) && <Check size={12} />}
                            </button>
                          </div>
                        ))}
                        {filteredUsers.length === 0 && (
                          <div className="p-3 text-center text-gray-500 text-sm">
                            {userSearch ? "No users found" : "No users available"}
                          </div>
                        )}
                      </div>

                      {/* Selected Users Display */}
                      {tempFilters.user_ids.length > 0 && (
                        <div className="space-y-2">
                          <span className="text-sm font-medium text-gray-700">Selected Users:</span>
                          <div className="flex flex-wrap gap-2">
                            {tempFilters.user_ids.map((userId) => {
                              const user = usersData?.data?.items?.find((u) => u.id === userId)
                              return (
                                <span
                                  key={userId}
                                  className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded-full"
                                >
                                  {user?.name || `User ${userId}`}
                                  <X
                                    className="w-3 h-3 cursor-pointer hover:text-red-500"
                                    onClick={() => removeUserFilter(userId)}
                                  />
                                </span>
                              )
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Modal Actions */}
                <div className="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                  <button
                    onClick={clearAllFilters}
                    className="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                  >
                    Clear All
                  </button>
                  <div className="flex gap-3">
                    <button
                      onClick={closeFilterModal}
                      className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      Cancel
                    </button>
                    <button
                      onClick={applyFilters}
                      className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                      Apply Filters
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Campaign Modal */}
        <CampaignModal
          isOpen={isCampaignModalOpen}
          onClose={closeCampaignModal}
          onSubmit={handleCampaignSubmit}
          emailAccounts={emailAccountsData?.data.items || []}
          selectedCount={allSelected ? totalCount - deselectedIds.length : selectedIds.length}
          isLoading={isLoadingSendCampaign}
        />

        {/* Delete Modal */}
        <DeleteModal
          isOpen={isShowDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onDelete={handleConfirmDelete}
          isLoadingDelete={isLoadingDelete}
        />

        {/* Bulk Delete Modal */}
        <BulkDeleteModal
          isOpen={isShowBulkDeleteModal}
          onClose={handleCancelBulkDelete}
          onDelete={handleConfirmBulkDelete}
          isLoadingDelete={isLoadingBulkDelete}
          count={allSelected ? totalCount - deselectedIds.length : selectedIds.length}
        />

        {/* Import Modal */}
        {isShowImportModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
            <div className="bg-white p-6 rounded-xl shadow-2xl w-full max-w-md">
              <h2 className="text-xl font-semibold mb-6 text-center">Import People</h2>
              <form
                onSubmit={async (e) => {
                  e.preventDefault()
                  const form = e.target as HTMLFormElement
                  const fileInput = form.elements.namedItem("file") as HTMLInputElement

                  if (!fileInput?.files?.[0]) {
                    toast.error("Please select a file to upload")
                    return
                  }

                  const formData = new FormData()
                  formData.append("file", fileInput.files[0])

                  try {
                    const response = await importUsers(formData).unwrap()
                    toast.success(response.message || "Import successful")
                    setShowImportModal(false)
                    fetchPeople()
                  } catch (error: any) {
                    toast.error(error?.data?.message || "Import failed")
                  }
                }}
              >
                <input
                  type="file"
                  name="file"
                  id="file-upload"
                  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  className="hidden"
                  onChange={(e) => {
                    const fileLabel = document.getElementById("file-name")
                    if (fileLabel && e.target.files?.[0]) {
                      fileLabel.textContent = e.target.files[0].name
                    }
                  }}
                />
                <label
                  htmlFor="file-upload"
                  className="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition mb-4"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-10 w-10 text-blue-500 mb-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"
                    />
                  </svg>
                  <span className="text-gray-600 text-sm">Click to upload CSV or Excel file</span>
                  <span id="file-name" className="text-xs text-gray-500 mt-2"></span>
                </label>
                <div className="flex justify-end gap-2">
                  <button
                    type="submit"
                    className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md transition"
                  >
                    Upload
                  </button>
                  <button
                    type="button"
                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-md transition"
                    onClick={() => setShowImportModal(false)}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
