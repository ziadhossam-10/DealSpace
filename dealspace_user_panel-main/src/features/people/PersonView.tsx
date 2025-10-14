"use client"
import React, { useState } from "react"
import { useParams, useSearchParams } from "react-router"
import {
  useGetPersonByIdQuery,
  useAddPersonEmailMutation,
  useUpdatePersonEmailMutation,
  useDeletePersonEmailMutation,
  useAddPersonPhoneMutation,
  useUpdatePersonPhoneMutation,
  useDeletePersonPhoneMutation,
  useAddPersonAddressMutation,
  useUpdatePersonAddressMutation,
  useDeletePersonAddressMutation,
  useAddPersonTagMutation,
  useUpdatePersonTagMutation,
  useDeletePersonTagMutation,
  useUpdatePersonMutation,
  useAddPersonCollaboratorMutation,
  useDeletePersonCollaboratorMutation,
  useGetPersonFilesQuery,
  useAddPersonFileMutation,
  useDeletePersonFileMutation,
  useGetPersonEventsQuery,
} from "./peopleApi"

// Import deals API hooks and types
import {
  useGetDealsQuery,
  useCreateDealMutation,
  useUpdateDealMutation,
  useDeleteDealMutation,
  useGetDealTypesQuery,
  useGetDealStagesQuery,
  useGetUsersQuery as useGetDealUsersQuery,
} from "../deals/dealsApi"

// Import appointments API hooks
import {
  useGetAppointmentsQuery,
  useCreateAppointmentMutation,
  useUpdateAppointmentMutation,
  useDeleteAppointmentMutation,
  type Appointment,
} from "./appointmentsApi"

// Import tasks API hooks
import {
  useGetTasksQuery,
  useCreateTaskMutation,
  useUpdateTaskMutation,
  useDeleteTaskMutation,
  useCompleteTaskMutation,
  type Task,
} from "./tasksApi"

// Import correct types from deals module
import type { Deal } from "../../types/deals"
import { useGetUsersQuery } from "../users/usersApi"
import { useGetCustomFieldsQuery, useSetPersonCustomFieldValuesMutation } from "../customFields/customFieldsApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import type { UpdatePersonRequest, CustomField } from "../../types/people"
import { useGetPeopleQuery } from "../people/peopleApi"

// Import all the existing components
import { PersonContactInfo } from "./components/personContactInfo"
import { PersonDetailsSection } from "./components/personDetailsSection"
import { PersonBackgroundSection } from "./components/personBackgroundSection"
import { PersonCustomFieldsSection } from "./components/personCustomFieldSection"
import { PersonActivityLog } from "./components/personActivityLog"
import { PersonSidebarActions } from "./components/personSidebarActions"
import { EmailDialog, PhoneDialog, AddressDialog, TagDialog, PersonDetailsDialog } from "./components/personDialogs"
import { CustomFieldsDialog, BackgroundDialog, CollaboratorDialog, Toast } from "./components/additionalDialogs"
import { FileUploadDialog } from "./components/FileUploadDialog"
import { PersonDealModal } from "./components/PersonDealModal"
import { AppointmentModal } from "./components/AppointmentModal"
import { TaskModal } from "./components/TaskModal"
import type { Person } from "../../types/people"
import { ASSETS_URL } from "../../utils/helpers"
import { useGetAppointmentTypesQuery } from "../appointmentTypes/appointmentTypesApi"
import { useGetAppointmentOutcomesQuery } from "../appointmentOutcomes/appointmentOutcomesApi"

// Define types for our data (keep only the ones not imported from deals)
interface EmailData {
  id: number
  value: string
  type: string
  is_primary: boolean
}

interface PhoneData {
  id: number
  value: string
  type: string
  is_primary: boolean
}

interface AddressData {
  id: number
  street_address: string
  city: string
  state: string
  postal_code: string
  country: string
  type: string
  is_primary: boolean
}

interface TagData {
  id: number
  name: string
  color: string
  description: string
}

interface FileData {
  id: number
  name: string
  description: string | null
  size: number
  type: string
  path: string
}

interface AppointmentData {
  id: number
  title: string
  description: string
  start: string
  end: string
  all_day: boolean
  location: string
  formatted_date_range: string
  status: string
  invitee_names: string[]
  type?: { id: number; name: string }
  outcome?: { id: number; name: string }
  user_invitees: Array<{
    id: number
    name: string
    email: string
    response_status: string
    responded_at: string | null
  }>
  person_invitees: Array<{
    id: number
    name: string
    email: string | null
    response_status: string
    responded_at: string | null
  }>
  created_by_id: number
  type_id: number | null
  outcome_id: number | null
  check_conflicts: boolean
  // For the modal
  user_ids: number[]
  person_ids: number[]
  user_ids_to_delete?: number[]
  person_ids_to_delete?: number[]
}

interface TaskData {
  id: number
  person_id: number
  assigned_user_id: number
  name: string
  type: string
  is_completed: boolean
  due_date: string
  due_date_time: string
  remind_seconds_before: number
  notes: string | null
  status: string
  priority: string
  formatted_due_date: string
  is_overdue: boolean
  is_due_today: boolean
  is_due_soon: boolean
  is_future: boolean
  reminder_time: string
  needs_reminder_now: boolean
  created_at: string
  updated_at: string
  assigned_user: {
    id: number
    name: string
    email: string
    avatar: string | null
    role: number
    role_name: string
  }
}

interface PeopleFilters {
  search?: string
  stage_id?: number | null
  team_id?: number | null
  user_ids?: number[]
  deal_type_id?: number | null
}

export default function PersonView() {
  const { personId } = useParams<{ personId: string }>()
  const [searchParams] = useSearchParams()
  const numericPersonId = Number(personId)

  // Parse filters from URL parameters
  const filters: PeopleFilters = React.useMemo(() => {
    const urlFilters: PeopleFilters = {}

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

  // Dialog states
  const [emailDialog, setEmailDialog] = useState<{ isOpen: boolean; data: EmailData | null }>({
    isOpen: false,
    data: null,
  })

  const [phoneDialog, setPhoneDialog] = useState<{ isOpen: boolean; data: PhoneData | null }>({
    isOpen: false,
    data: null,
  })

  const [addressDialog, setAddressDialog] = useState<{ isOpen: boolean; data: AddressData | null }>({
    isOpen: false,
    data: null,
  })

  const [tagDialog, setTagDialog] = useState<{ isOpen: boolean; data: TagData | null }>({
    isOpen: false,
    data: null,
  })

  // Person details dialog state
  const [personDetailsDialog, setPersonDetailsDialog] = useState<{ isOpen: boolean; data: Person | null }>({
    isOpen: false,
    data: null,
  })

  // Collaborator dialog state
  const [collaboratorDialog, setCollaboratorDialog] = useState<{ isOpen: boolean }>({
    isOpen: false,
  })

  // Background dialog state
  const [backgroundDialog, setBackgroundDialog] = useState<{ isOpen: boolean; data: string }>({
    isOpen: false,
    data: "",
  })

  // Custom fields dialog state
  const [customFieldsDialog, setCustomFieldsDialog] = useState<{ isOpen: boolean }>({
    isOpen: false,
  })

  // File upload dialog state
  const [fileUploadDialog, setFileUploadDialog] = useState<{ isOpen: boolean }>({
    isOpen: false,
  })

  // Deal dialog state - using imported Deal type
  const [dealDialog, setDealDialog] = useState<{ isOpen: boolean; data: Deal | null }>({
    isOpen: false,
    data: null,
  })

  // Appointment dialog state
  const [appointmentDialog, setAppointmentDialog] = useState<{ isOpen: boolean; data: AppointmentData | null }>({
    isOpen: false,
    data: null,
  })

  // Task dialog state
  const [taskDialog, setTaskDialog] = useState<{ isOpen: boolean; data: Task | null }>({
    isOpen: false,
    data: null,
  })

  // Deal pagination state
  const [dealPage, setDealPage] = useState(1)

  // Appointment pagination state
  const [appointmentPage, setAppointmentPage] = useState(1)

  // Task pagination state
  const [taskPage, setTaskPage] = useState(1)

  const [collaboratorSearchTerm, setCollaboratorSearchTerm] = useState("")
  const [showCollaboratorSearch, setShowCollaboratorSearch] = useState(false)
  const [userSearchTerm, setUserSearchTerm] = useState("")

  // Toast state
  const [toast, setToast] = useState<{ visible: boolean; message: string; type: "success" | "error" }>({
    visible: false,
    message: "",
    type: "success",
  })

  // API queries - now includes filters
  const {
    data: contact,
    isLoading,
    error,
    refetch: fetchPerson,
  } = useGetPersonByIdQuery({
    id: numericPersonId,
    filters,
  })

  const { data: filesData, isLoading: isLoadingFiles } = useGetPersonFilesQuery(numericPersonId)

  // Deal-related API queries
  const {
    data: dealsData,
    isLoading: isLoadingDeals,
    refetch: refetchDeals,
  } = useGetDealsQuery({
    page: dealPage,
    per_page: 10,
  })

  const { data: dealTypesData } = useGetDealTypesQuery()
  const { data: dealStagesData } = useGetDealStagesQuery(0, {
    skip: true,
  })
  const { data: dealUsersData, isLoading: isLoadingDealUsers } = useGetDealUsersQuery({
    page: 1,
    per_page: 100,
  })

  // Appointment-related API queries
  const {
    data: appointmentsData,
    isLoading: isLoadingAppointments,
    refetch: refetchAppointments,
  } = useGetAppointmentsQuery({
    page: appointmentPage,
    per_page: 10,
    person_id: numericPersonId,
  })

  const { data: appointmentTypesData } = useGetAppointmentTypesQuery()
  const { data: appointmentOutcomesData } = useGetAppointmentOutcomesQuery()

  // Task-related API queries
  const {
    data: tasksData,
    isLoading: isLoadingTasks,
    refetch: refetchTasks,
  } = useGetTasksQuery({
    page: taskPage,
    per_page: 10,
    person_id: numericPersonId,
  })

  // API mutations
  const [addPersonEmail] = useAddPersonEmailMutation()
  const [updatePersonEmail] = useUpdatePersonEmailMutation()
  const [deletePersonEmail] = useDeletePersonEmailMutation()
  const [addPersonPhone] = useAddPersonPhoneMutation()
  const [updatePersonPhone] = useUpdatePersonPhoneMutation()
  const [deletePersonPhone] = useDeletePersonPhoneMutation()
  const [addPersonAddress] = useAddPersonAddressMutation()
  const [updatePersonAddress] = useUpdatePersonAddressMutation()
  const [deletePersonAddress] = useDeletePersonAddressMutation()
  const [addPersonTag] = useAddPersonTagMutation()
  const [updatePersonTag] = useUpdatePersonTagMutation()
  const [deletePersonTag] = useDeletePersonTagMutation()
  const [updatePerson] = useUpdatePersonMutation()
  const [addPersonCollaborator] = useAddPersonCollaboratorMutation()
  const [deletePersonCollaborator] = useDeletePersonCollaboratorMutation()
  const [addPersonFile, { isLoading: isUploadingFile }] = useAddPersonFileMutation()
  const [deletePersonFile] = useDeletePersonFileMutation()

  // Deal mutations
  const [createDeal, { isLoading: isCreatingDeal }] = useCreateDealMutation()
  const [updateDeal, { isLoading: isUpdatingDeal }] = useUpdateDealMutation()
  const [deleteDeal] = useDeleteDealMutation()

  // Appointment mutations
  const [createAppointment, { isLoading: isCreatingAppointment }] = useCreateAppointmentMutation()
  const [updateAppointment, { isLoading: isUpdatingAppointment }] = useUpdateAppointmentMutation()
  const [deleteAppointment] = useDeleteAppointmentMutation()

  // Task mutations
  const [createTask, { isLoading: isCreatingTask }] = useCreateTaskMutation()
  const [updateTask, { isLoading: isUpdatingTask }] = useUpdateTaskMutation()
  const [deleteTask] = useDeleteTaskMutation()
  const [completeTask] = useCompleteTaskMutation()

  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    { search: userSearchTerm, page: 1, per_page: 50 },
    { skip: false }, // Always fetch users for appointments
  )

  // Separate query for collaborator search
  const { data: collaboratorUsersData, isLoading: isLoadingCollaboratorUsers } = useGetUsersQuery(
    { search: collaboratorSearchTerm, page: 1, per_page: 50 },
    { skip: !showCollaboratorSearch },
  )

  const { data: customFieldsData } = useGetCustomFieldsQuery()
  const [setPersonCustomFieldValues] = useSetPersonCustomFieldValuesMutation()

  const { data: peopleData, isLoading: isLoadingPeople } = useGetPeopleQuery({
    page: 1,
    per_page: 100,
  })

  // Combine custom fields with their values
  const customFieldsWithValues = React.useMemo(() => {
    if (!customFieldsData?.data || !contact?.data?.person) return []

    return customFieldsData.data.map((field) => {
      const personCustomField = contact.data.person.custom_fields?.find(
        (pcf: CustomField) => pcf.custom_field_id === field.id,
      )
      return {
        ...field,
        value: personCustomField?.value || "",
      }
    })
  }, [customFieldsData?.data, contact?.data?.person])

  // Process deals data - filter deals for this person
  const allDeals = dealsData?.data?.items || []
  const deals = allDeals.filter((deal) => deal.people?.some((person) => person.id === numericPersonId))
  const totalDeals = deals.length
  const hasMoreDeals = dealPage < (dealsData?.data?.meta?.last_page || 1)

  // Process appointments data
  const appointments = appointmentsData?.data?.items || []
  const totalAppointments = appointmentsData?.data?.meta?.total || 0
  const hasMoreAppointments = appointmentPage < (appointmentsData?.data?.meta?.last_page || 1)

  // Process tasks data
  const tasks = tasksData?.data?.items || []
  const totalTasks = tasksData?.data?.meta?.total || 0
  const hasMoreTasks = taskPage < (tasksData?.data?.meta?.last_page || 1)

  const showToast = (message: string, type: "success" | "error" = "success") => {
    setToast({ visible: true, message, type })
  }

  const hideToast = () => {
    setToast({ visible: false, message: "", type: "success" })
  }

  // Email handlers
  const handleAddEmail = async (data: Omit<EmailData, "id">) => {
    try {
      await addPersonEmail({ personId: numericPersonId, data }).unwrap()
      showToast("Email added successfully")
    } catch (error) {
      showToast("Failed to add email", "error")
      console.error(error)
    }
  }

  const handleEditEmail = async (id: number, data: Omit<EmailData, "id">) => {
    try {
      await updatePersonEmail({ personId: numericPersonId, emailId: id, data }).unwrap()
      showToast("Email updated successfully")
    } catch (error) {
      showToast("Failed to update email", "error")
      console.error(error)
    }
  }

  const handleDeleteEmail = async (id: number) => {
    if (window.confirm("Are you sure you want to delete this email?")) {
      try {
        await deletePersonEmail({ personId: numericPersonId, emailId: id }).unwrap()
        showToast("Email deleted successfully")
      } catch (error) {
        showToast("Failed to delete email", "error")
        console.error(error)
      }
    }
  }

  // Phone handlers
  const handleAddPhone = async (data: Omit<PhoneData, "id">) => {
    try {
      await addPersonPhone({ personId: numericPersonId, data }).unwrap()
      showToast("Phone added successfully")
    } catch (error) {
      showToast("Failed to add phone", "error")
      console.error(error)
    }
  }

  const handleEditPhone = async (id: number, data: Omit<PhoneData, "id">) => {
    try {
      await updatePersonPhone({ personId: numericPersonId, phoneId: id, data }).unwrap()
      showToast("Phone updated successfully")
    } catch (error) {
      showToast("Failed to update phone", "error")
      console.error(error)
    }
  }

  const handleDeletePhone = async (id: number) => {
    if (window.confirm("Are you sure you want to delete this phone?")) {
      try {
        await deletePersonPhone({ personId: numericPersonId, phoneId: id }).unwrap()
        showToast("Phone deleted successfully")
      } catch (error) {
        showToast("Failed to delete phone", "error")
        console.error(error)
      }
    }
  }

  // Address handlers
  const handleAddAddress = async (data: Omit<AddressData, "id">) => {
    try {
      await addPersonAddress({ personId: numericPersonId, data }).unwrap()
      showToast("Address added successfully")
    } catch (error) {
      showToast("Failed to add address", "error")
      console.error(error)
    }
  }

  const handleEditAddress = async (id: number, data: Omit<AddressData, "id">) => {
    try {
      await updatePersonAddress({ personId: numericPersonId, addressId: id, data }).unwrap()
      showToast("Address updated successfully")
    } catch (error) {
      showToast("Failed to update address", "error")
      console.error(error)
    }
  }

  const handleDeleteAddress = async (id: number) => {
    if (window.confirm("Are you sure you want to delete this address?")) {
      try {
        await deletePersonAddress({ personId: numericPersonId, addressId: id }).unwrap()
        showToast("Address deleted successfully")
      } catch (error) {
        showToast("Failed to delete address", "error")
        console.error(error)
      }
    }
  }

  // Tag handlers
  const handleAddTag = async (data: Omit<TagData, "id">) => {
    try {
      await addPersonTag({ personId: numericPersonId, data }).unwrap()
      showToast("Tag added successfully")
    } catch (error) {
      showToast("Failed to add tag", "error")
      console.error(error)
    }
  }

  const handleEditTag = async (id: number, data: Omit<TagData, "id">) => {
    try {
      await updatePersonTag({ personId: numericPersonId, tagId: id, data }).unwrap()
      showToast("Tag updated successfully")
    } catch (error) {
      showToast("Failed to update tag", "error")
      console.error(error)
    }
  }

  const handleDeleteTag = async (id: number) => {
    if (window.confirm("Are you sure you want to delete this tag?")) {
      try {
        await deletePersonTag({ personId: numericPersonId, tagId: id }).unwrap()
        showToast("Tag deleted successfully")
      } catch (error) {
        showToast("Failed to delete tag", "error")
        console.error(error)
      }
    }
  }

  // Person details handler
  const handleUpdatePersonDetails = async (data: Partial<UpdatePersonRequest>) => {
    try {
      await updatePerson({
        id: numericPersonId,
        ...data,
      }).unwrap()
      showToast("Person details updated successfully")
      fetchPerson()
    } catch (error) {
      showToast("Failed to update person details", "error")
      console.error(error)
    }
  }

  // Background handler
  const handleUpdateBackground = async (backgroundText: string) => {
    try {
      await updatePerson({
        id: numericPersonId,
        background: backgroundText,
      }).unwrap()
      showToast("Background updated successfully")
      fetchPerson()
    } catch (error) {
      showToast("Failed to update background", "error")
      console.error(error)
    }
  }

  // Custom fields handler
  const handleUpdateCustomFields = async (customFieldValues: Array<{ id: number; value: string }>) => {
    try {
      await setPersonCustomFieldValues({
        personId: numericPersonId,
        custom_fields: customFieldValues,
      }).unwrap()
      showToast("Custom fields updated successfully")
      fetchPerson()
    } catch (error) {
      showToast("Failed to update custom fields", "error")
      console.error(error)
    }
  }

  // Collaborator handlers
  const handleAddCollaborator = async (userId: number) => {
    try {
      await addPersonCollaborator({ personId: numericPersonId, userId }).unwrap()
      showToast("Collaborator added successfully")
      setCollaboratorDialog({ isOpen: false })
      setShowCollaboratorSearch(false)
      setCollaboratorSearchTerm("")
    } catch (error) {
      showToast("Failed to add collaborator", "error")
      console.error(error)
    }
  }

  const handleDeleteCollaborator = async (collaboratorId: number) => {
    if (window.confirm("Are you sure you want to remove this collaborator?")) {
      try {
        await deletePersonCollaborator({ personId: numericPersonId, collaboratorId }).unwrap()
        showToast("Collaborator removed successfully")
      } catch (error) {
        showToast("Failed to remove collaborator", "error")
        console.error(error)
      }
    }
  }

  // File management handlers
  const handleAddFile = async (formData: FormData) => {
    try {
      await addPersonFile({ personId: numericPersonId, formData }).unwrap()
      showToast("File uploaded successfully")
    } catch (error) {
      showToast("Failed to upload file", "error")
      console.error(error)
      throw error
    }
  }

  const handleDeleteFile = async (fileId: number) => {
    if (window.confirm("Are you sure you want to delete this file?")) {
      try {
        await deletePersonFile({ personId: numericPersonId, fileId }).unwrap()
        showToast("File deleted successfully")
      } catch (error) {
        showToast("Failed to delete file", "error")
        console.error(error)
      }
    }
  }

  const handleDownloadFile = (file: FileData) => {
    const downloadUrl = `${ASSETS_URL}/storage/${file.path}`
    const link = document.createElement("a")
    link.href = downloadUrl
    link.download = file.name
    link.target = "_blank"
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  // Deal handlers
  const handleAddDeal = () => {
    setDealDialog({ isOpen: true, data: null })
  }

  const handleEditDeal = (deal: Deal) => {
    setDealDialog({ isOpen: true, data: deal })
  }

  const handleDeleteDeal = async (deal: Deal) => {
    if (window.confirm("Are you sure you want to delete this deal?")) {
      try {
        await deleteDeal(deal.id).unwrap()
        showToast("Deal deleted successfully")
        refetchDeals()
      } catch (error) {
        showToast("Failed to delete deal", "error")
        console.error(error)
      }
    }
  }

  const handleDealSubmit = async (dealData: any) => {
    try {
      if (dealDialog.data) {
        // Update existing deal
        await updateDeal({ id: dealDialog.data.id, ...dealData }).unwrap()
        showToast("Deal updated successfully")
      } else {
        // Create new deal
        await createDeal(dealData).unwrap()
        showToast("Deal created successfully")
      }
      refetchDeals()
    } catch (error) {
      showToast(dealDialog.data ? "Failed to update deal" : "Failed to create deal", "error")
      console.error(error)
      throw error
    }
  }

  const handleLoadMoreDeals = () => {
    setDealPage((prev) => prev + 1)
  }

  // Appointment handlers
  const handleAddAppointment = () => {
    setAppointmentDialog({ isOpen: true, data: null })
  }

  const handleEditAppointment = (appointment: Appointment) => {
    // Transform the appointment data to match the modal's expected format
    const transformedAppointment: AppointmentData = {
      id: appointment.id,
      title: appointment.title,
      description: appointment.description,
      start: appointment.start,
      end: appointment.end,
      all_day: appointment.all_day,
      location: appointment.location,
      formatted_date_range: appointment.formatted_date_range,
      status: appointment.status,
      invitee_names: appointment.invitee_names,
      type: appointment.type,
      outcome: appointment.outcome,
      type_id: appointment.type?.id || null,
      outcome_id: appointment.outcome?.id || null,
      created_by_id: appointment.created_by_id || 1,
      user_invitees: appointment.user_invitees || [],
      person_invitees: appointment.person_invitees || [],
      check_conflicts: appointment.check_conflicts || true,
      // Add the required fields for the modal
      user_ids: appointment.user_invitees?.map((user) => user.id) || [],
      person_ids: appointment.person_invitees?.map((person) => person.id) || [],
      user_ids_to_delete: [],
      person_ids_to_delete: [],
    }
    setAppointmentDialog({ isOpen: true, data: transformedAppointment })
  }

  const handleDeleteAppointment = async (appointment: Appointment) => {
    if (window.confirm("Are you sure you want to delete this appointment?")) {
      try {
        await deleteAppointment(appointment.id).unwrap()
        showToast("Appointment deleted successfully")
        refetchAppointments()
      } catch (error) {
        showToast("Failed to delete appointment", "error")
        console.error(error)
      }
    }
  }

  const handleAppointmentSubmit = async (appointmentData: any) => {
    try {
      // Add the current user as the creator
      const submitData = {
        ...appointmentData,
        created_by_id: 1, // You might want to get this from current user context
      }

      if (appointmentDialog.data) {
        // Update existing appointment
        await updateAppointment({ id: appointmentDialog.data.id, ...submitData }).unwrap()
        showToast("Appointment updated successfully")
      } else {
        // Create new appointment
        await createAppointment(submitData).unwrap()
        showToast("Appointment created successfully")
      }
      refetchAppointments()
      setAppointmentDialog({ isOpen: false, data: null })
    } catch (error) {
      showToast(appointmentDialog.data ? "Failed to update appointment" : "Failed to create appointment", "error")
      console.error(error)
      throw error
    }
  }

  const handleLoadMoreAppointments = () => {
    setAppointmentPage((prev) => prev + 1)
  }

  // Task handlers
  const handleAddTask = () => {
    setTaskDialog({ isOpen: true, data: null })
  }

  const handleEditTask = (task: Task) => {
    setTaskDialog({ isOpen: true, data: task })
  }

  const handleDeleteTask = async (task: TaskData) => {
    if (window.confirm("Are you sure you want to delete this task?")) {
      try {
        await deleteTask(task.id).unwrap()
        showToast("Task deleted successfully")
        refetchTasks()
      } catch (error) {
        showToast("Failed to delete task", "error")
        console.error(error)
      }
    }
  }

  const handleCompleteTask = async (task: TaskData) => {
    try {
      await completeTask(task.id).unwrap()
      showToast("Task marked as completed")
      refetchTasks()
    } catch (error) {
      showToast("Failed to complete task", "error")
      console.error(error)
    }
  }

  const handleTaskSubmit = async (taskData: any) => {
    try {
      if (taskDialog.data) {
        // Update existing task
        await updateTask({ id: taskDialog.data.id, ...taskData }).unwrap()
        showToast("Task updated successfully")
      } else {
        // Create new task
        await createTask(taskData).unwrap()
        showToast("Task created successfully")
      }
      refetchTasks()
      setTaskDialog({ isOpen: false, data: null })
    } catch (error) {
      showToast(taskDialog.data ? "Failed to update task" : "Failed to create task", "error")
      console.error(error)
      throw error
    }
  }

  const handleLoadMoreTasks = () => {
    setTaskPage((prev) => prev + 1)
  }

    const [eventPage, setEventPage] = useState(1)
  const [eventFilters, setEventFilters] = useState({
    search: "",
    type: "",
    source: "",
    date_from: "",
    date_to: "",
  })

  const {
    data: eventsData,
    isLoading: isLoadingEvents,
    refetch: refetchEvents,
  } = useGetPersonEventsQuery({
    person_id: numericPersonId,
    page: eventPage,
    per_page: 15,
    ...eventFilters,
  })

  const events = eventsData?.data?.items || []
  const totalEvents = eventsData?.data?.meta?.total || 0
  const hasMoreEvents = eventPage < (eventsData?.data?.meta?.last_page || 1)

  const handleLoadMoreEvents = () => {
    setEventPage((prev) => prev + 1)
  }

  const handleEventFilterChange = (filters:any) => {
    setEventFilters(filters)
    setEventPage(1) // Reset to first page when filtering
  }

  if (isLoading) return <TableLoader />
  if (error) return <TableErrorComponent />

  return (
    <div className="flex flex-col h-screen bg-gray-100">
      {/* Main Content */}
      <div className="flex flex-1 overflow-hidden">
        {/* Left Column - Person Information */}
        <div className="border-r bg-white overflow-y-auto">
          <PersonContactInfo
            contact={contact?.data?.person}
            onEditEmail={(email) => setEmailDialog({ isOpen: true, data: { ...email, id: Number(email.id) } })}
            onDeleteEmail={handleDeleteEmail}
            onAddEmail={() => setEmailDialog({ isOpen: true, data: null })}
            onEditPhone={(phone) => setPhoneDialog({ isOpen: true, data: { ...phone, id: Number(phone.id) } })}
            onDeletePhone={handleDeletePhone}
            onAddPhone={() => setPhoneDialog({ isOpen: true, data: null })}
            onEditAddress={(address) =>
              setAddressDialog({ isOpen: true, data: { ...address, id: Number(address.id) } })
            }
            onDeleteAddress={handleDeleteAddress}
            onAddAddress={() => setAddressDialog({ isOpen: true, data: null })}
          />

          <PersonDetailsSection
            contact={contact?.data?.person}
            onEditDetails={() => setPersonDetailsDialog({ isOpen: true, data: contact?.data?.person || null })}
            onEditTag={(tag) => setTagDialog({ isOpen: true, data: { ...tag, id: Number(tag.id) } })}
            onDeleteTag={handleDeleteTag}
            onAddTag={() => setTagDialog({ isOpen: true, data: null })}
          />

          <PersonBackgroundSection
            background={contact?.data?.person?.background}
            onEditBackground={() =>
              setBackgroundDialog({ isOpen: true, data: contact?.data?.person?.background || "" })
            }
          />

          <PersonCustomFieldsSection
            customFieldsWithValues={customFieldsWithValues}
            onEditCustomFields={() => setCustomFieldsDialog({ isOpen: true })}
          />
        </div>

        {/* Middle Column - Activity Log */}
        <PersonActivityLog
          contact={contact?.data?.person}
          navigation={contact?.data?.navigation}
          filters={filters}
          onToast={showToast}
        />

        {/* Right Column - Actions */}
        <PersonSidebarActions
          contact={contact?.data?.person}
          onAddCollaborator={() => setCollaboratorDialog({ isOpen: true })}
          onDeleteCollaborator={handleDeleteCollaborator}
          files={filesData?.data || []}
          onAddFile={() => setFileUploadDialog({ isOpen: true })}
          onDeleteFile={handleDeleteFile}
          onDownloadFile={handleDownloadFile}
          isLoadingFiles={isLoadingFiles}
          // Deal-related props
          deals={deals}
          totalDeals={totalDeals}
          currentDealPage={dealPage}
          hasMoreDeals={hasMoreDeals}
          isLoadingDeals={isLoadingDeals}
          isLoadingMoreDeals={false}
          onAddDeal={handleAddDeal}
          onEditDeal={handleEditDeal}
          onDeleteDeal={handleDeleteDeal}
          onLoadMoreDeals={handleLoadMoreDeals}
          // Appointment-related props
          appointments={appointments}
          totalAppointments={totalAppointments}
          currentAppointmentPage={appointmentPage}
          hasMoreAppointments={hasMoreAppointments}
          isLoadingAppointments={isLoadingAppointments}
          isLoadingMoreAppointments={false}
          onAddAppointment={handleAddAppointment}
          onEditAppointment={handleEditAppointment}
          onDeleteAppointment={handleDeleteAppointment}
          onLoadMoreAppointments={handleLoadMoreAppointments}
          // Task-related props
          tasks={tasks}
          totalTasks={totalTasks}
          currentTaskPage={taskPage}
          hasMoreTasks={hasMoreTasks}
          isLoadingTasks={isLoadingTasks}
          isLoadingMoreTasks={false}
          onAddTask={handleAddTask}
          onEditTask={handleEditTask}
          onDeleteTask={handleDeleteTask}
          onCompleteTask={handleCompleteTask}
          onLoadMoreTasks={handleLoadMoreTasks}
          // Events-related props
          events={events}
          totalEvents={totalEvents}
          currentEventPage={eventPage}
          hasMoreEvents={hasMoreEvents}
          isLoadingEvents={isLoadingEvents}
          isLoadingMoreEvents={false}
          onLoadMoreEvents={handleLoadMoreEvents}
          onEventFilterChange={handleEventFilterChange}
        />
      </div>

      {/* Dialogs */}
      <EmailDialog
        isOpen={emailDialog.isOpen}
        onClose={() => setEmailDialog({ isOpen: false, data: null })}
        onSubmit={emailDialog.data ? (data) => handleEditEmail(emailDialog.data!.id, data) : handleAddEmail}
        initialData={emailDialog.data}
      />

      <PhoneDialog
        isOpen={phoneDialog.isOpen}
        onClose={() => setPhoneDialog({ isOpen: false, data: null })}
        onSubmit={phoneDialog.data ? (data) => handleEditPhone(phoneDialog.data!.id, data) : handleAddPhone}
        initialData={phoneDialog.data}
      />

      <AddressDialog
        isOpen={addressDialog.isOpen}
        onClose={() => setAddressDialog({ isOpen: false, data: null })}
        onSubmit={addressDialog.data ? (data) => handleEditAddress(addressDialog.data!.id, data) : handleAddAddress}
        initialData={addressDialog.data}
      />

      <TagDialog
        isOpen={tagDialog.isOpen}
        onClose={() => setTagDialog({ isOpen: false, data: null })}
        onSubmit={tagDialog.data ? (data) => handleEditTag(tagDialog.data!.id, data) : handleAddTag}
        initialData={tagDialog.data}
      />

      <PersonDetailsDialog
        isOpen={personDetailsDialog.isOpen}
        onClose={() => setPersonDetailsDialog({ isOpen: false, data: null })}
        onSubmit={handleUpdatePersonDetails}
        initialData={personDetailsDialog.data}
      />

      <BackgroundDialog
        isOpen={backgroundDialog.isOpen}
        onClose={() => setBackgroundDialog({ isOpen: false, data: "" })}
        initialData={backgroundDialog.data}
        onSubmit={handleUpdateBackground}
      />

      <CustomFieldsDialog
        isOpen={customFieldsDialog.isOpen}
        onClose={() => setCustomFieldsDialog({ isOpen: false })}
        customFieldsWithValues={customFieldsWithValues}
        onSubmit={handleUpdateCustomFields}
      />

      <CollaboratorDialog
        isOpen={collaboratorDialog.isOpen}
        onClose={() => setCollaboratorDialog({ isOpen: false })}
        usersData={collaboratorUsersData}
        isLoadingUsers={isLoadingCollaboratorUsers}
        contact={contact}
        showCollaboratorSearch={showCollaboratorSearch}
        setShowCollaboratorSearch={setShowCollaboratorSearch}
        collaboratorSearchTerm={collaboratorSearchTerm}
        setCollaboratorSearchTerm={setCollaboratorSearchTerm}
        handleAddCollaborator={handleAddCollaborator}
      />

      {/* File Upload Dialog */}
      <FileUploadDialog
        isOpen={fileUploadDialog.isOpen}
        onClose={() => setFileUploadDialog({ isOpen: false })}
        onSubmit={handleAddFile}
        isUploading={isUploadingFile}
      />

      {/* Deal Dialog */}
      <PersonDealModal
        isOpen={dealDialog.isOpen}
        onClose={() => setDealDialog({ isOpen: false, data: null })}
        onSubmit={handleDealSubmit}
        deal={dealDialog.data}
        personId={numericPersonId}
        dealStages={dealStagesData?.data || []}
        users={dealUsersData?.data?.items || []}
        people={peopleData?.data?.items || []}
        isLoading={isCreatingDeal || isUpdatingDeal}
        isLoadingStages={false}
        isLoadingUsers={isLoadingDealUsers}
        isLoadingPeople={isLoadingPeople}
      />

      {/* Appointment Dialog */}
      <AppointmentModal
        isOpen={appointmentDialog.isOpen}
        onClose={() => setAppointmentDialog({ isOpen: false, data: null })}
        onSubmit={handleAppointmentSubmit}
        appointment={appointmentDialog.data}
        personId={numericPersonId}
        appointmentTypes={appointmentTypesData?.data || []}
        appointmentOutcomes={appointmentOutcomesData?.data || []}
        users={usersData?.data?.items || []}
        people={peopleData?.data?.items || []}
        isLoading={isCreatingAppointment || isUpdatingAppointment}
      />

      {/* Task Dialog */}
      <TaskModal
        isOpen={taskDialog.isOpen}
        onClose={() => setTaskDialog({ isOpen: false, data: null })}
        onSubmit={handleTaskSubmit}
        task={taskDialog.data}
        personId={numericPersonId}
        users={usersData?.data?.items || []}
        isLoading={isCreatingTask || isUpdatingTask}
      />

      {/* Toast */}
      {toast.visible && <Toast message={toast.message} type={toast.type} onClose={hideToast} />}
    </div>
  )
}
