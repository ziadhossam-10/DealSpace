"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { PenTool, Edit, Trash2, X, MessageSquare, Clock, User } from "lucide-react"
import { useGetNotesQuery, useCreateNoteMutation, useUpdateNoteMutation, useDeleteNoteMutation } from "../notesApi"
import { useGetUsersQuery } from "../../users/usersApi"
import { RichTextEditor } from "./richTextEditor"
import { ASSETS_URL, getInitials } from "../../../utils/helpers"
import type { Note, CreateNoteRequest, UpdateNoteRequest } from "../notesApi"

interface PersonNotesProps {
  personId: number
  onToast: (message: string, type?: "success" | "error") => void
}

interface NoteDialogProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<CreateNoteRequest, "person_id"> | Omit<UpdateNoteRequest, "id" | "person_id">) => void
  initialData?: Note | null
  personId: number
}

const NoteDialog = ({ isOpen, onClose, onSubmit, initialData, personId }: NoteDialogProps) => {
  const [subject, setSubject] = useState(initialData?.subject || "")
  const [body, setBody] = useState(initialData?.body || "")
  const [mentions, setMentions] = useState<number[]>(initialData?.mentions.map((m) => m.id) || [])

  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 100 })

  useEffect(() => {
    if (isOpen) {
      setSubject(initialData?.subject || "")
      setBody(initialData?.body || "")
      setMentions(initialData?.mentions.map((m) => m.id) || [])
    }
  }, [isOpen, initialData])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit({ subject, body, mentions })
    onClose()
  }

  const handleMentionSelect = (userId: number) => {
    if (!mentions.includes(userId)) {
      setMentions([...mentions, userId])
    }
  }

  const removeMention = (userId: number) => {
    setMentions(mentions.filter((id) => id !== userId))
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium">{initialData ? "Edit Note" : "Create Note"}</h3>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <label htmlFor="subject" className="block text-sm font-medium text-gray-700">
              Subject
            </label>
            <input
              id="subject"
              type="text"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="Enter note subject"
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
          </div>

          <div className="space-y-2">
            <label className="block text-sm font-medium text-gray-700">Note Content</label>
            <div className="border border-gray-300 rounded-md overflow-hidden">
              <RichTextEditor
                value={body}
                onChange={setBody}
                onMentionSelect={handleMentionSelect}
                users={usersData?.data?.items || []}
                placeholder="Type your note here... Use @ to mention users"
              />
            </div>
          </div>

          {mentions.length > 0 && (
            <div className="space-y-2">
              <label className="block text-sm font-medium text-gray-700">Mentioned Users</label>
              <div className="flex flex-wrap gap-2">
                {mentions.map((userId) => {
                  const user = usersData?.data?.items?.find((u: any) => u.id === userId)
                  if (!user) return null

                  return (
                    <div
                      key={userId}
                      className="flex items-center space-x-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-sm"
                    >
                      <div className="w-5 h-5 bg-blue-200 rounded-full flex items-center justify-center text-xs">
                        {user.avatar ? (
                          <img
                            src={ASSETS_URL + "/storage/" + user.avatar || "/placeholder.svg"}
                            alt={user.name}
                            className="w-full h-full rounded-full object-cover"
                          />
                        ) : (
                          getInitials(user.name)
                        )}
                      </div>
                      <span>{user.name}</span>
                      <button
                        type="button"
                        onClick={() => removeMention(userId)}
                        className="text-blue-600 hover:text-blue-800"
                      >
                        <X size={12} />
                      </button>
                    </div>
                  )
                })}
              </div>
            </div>
          )}

          <div className="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              {initialData ? "Update Note" : "Create Note"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export const PersonNotes = ({ personId, onToast }: PersonNotesProps) => {
  const [noteDialog, setNoteDialog] = useState<{ isOpen: boolean; data: Note | null }>({
    isOpen: false,
    data: null,
  })

  const { data: notesData, isLoading, refetch } = useGetNotesQuery({ person_id: personId })
  const [createNote] = useCreateNoteMutation()
  const [updateNote] = useUpdateNoteMutation()
  const [deleteNote] = useDeleteNoteMutation()

  const handleCreateNote = async (data: Omit<CreateNoteRequest, "person_id">) => {
    try {
      await createNote({ ...data, person_id: personId }).unwrap()
      onToast("Note created successfully")
      refetch()
    } catch (error) {
      onToast("Failed to create note", "error")
      console.error(error)
    }
  }

  const handleUpdateNote = async (data: Omit<UpdateNoteRequest, "id" | "person_id">) => {
    if (!noteDialog.data) return

    try {
      await updateNote({
        id: noteDialog.data.id,
        person_id: personId,
        ...data,
      }).unwrap()
      onToast("Note updated successfully")
      refetch()
    } catch (error) {
      onToast("Failed to update note", "error")
      console.error(error)
    }
  }

  const handleDeleteNote = async (noteId: number) => {
    if (window.confirm("Are you sure you want to delete this note?")) {
      try {
        await deleteNote(noteId).unwrap()
        onToast("Note deleted successfully")
        refetch()
      } catch (error) {
        onToast("Failed to delete note", "error")
        console.error(error)
      }
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  return (
    <div className="space-y-4">
      {/* Create Note Button */}
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-medium flex items-center">
          <MessageSquare size={20} className="mr-2" />
          Notes
        </h3>
        <button
          onClick={() => setNoteDialog({ isOpen: true, data: null })}
          className="flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          <PenTool size={16} className="mr-1" />
          Create Note
        </button>
      </div>

      {/* Notes List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="text-center py-4 text-gray-500">Loading notes...</div>
        ) : notesData?.data?.items?.length ? (
          notesData.data.items.map((note) => (
            <div key={note.id} className="border border-gray-200 rounded-lg p-4 bg-[#cccccc0a] shadow-sm">
              <div className="flex justify-between items-start mb-2">
                <h4 className="font-medium text-gray-900">{note.subject}</h4>
                <div className="flex items-center space-x-1">
                  <button
                    onClick={() => setNoteDialog({ isOpen: true, data: note })}
                    className="p-1 text-gray-400 hover:text-blue-500 rounded"
                  >
                    <Edit size={14} />
                  </button>
                  <button
                    onClick={() => handleDeleteNote(note.id)}
                    className="p-1 text-gray-400 hover:text-red-500 rounded"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>

              <div
                className="text-gray-700 mb-3 prose prose-sm max-w-none"
                dangerouslySetInnerHTML={{ __html: note.body }}
              />

              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center space-x-1">
                    <User size={14} />
                    <span>By {note.created_by.name}</span>
                  </div>
                  {note.created_at && (
                    <div className="flex items-center space-x-1">
                      <Clock size={14} />
                      <span>{formatDate(note.created_at)}</span>
                    </div>
                  )}
                </div>

                {note.mentions.length > 0 && (
                  <div className="flex items-center space-x-1">
                    <span className="text-xs">Mentions:</span>
                    <div className="flex -space-x-1">
                      {note.mentions.slice(0, 3).map((mention) => (
                        <div
                          key={mention.id}
                          className="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs border-2 border-white"
                          title={mention.name}
                        >
                          {mention.avatar ? (
                            <img
                              src={ASSETS_URL + "/storage/" + mention.avatar || "/placeholder.svg"}
                              alt={mention.name}
                              className="w-full h-full rounded-full object-cover"
                            />
                          ) : (
                            getInitials(mention.name)
                          )}
                        </div>
                      ))}
                      {note.mentions.length > 3 && (
                        <div className="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs border-2 border-white">
                          +{note.mentions.length - 3}
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          ))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <MessageSquare size={48} className="mx-auto mb-2 text-gray-300" />
            <p>No notes yet</p>
            <p className="text-sm">Create your first note to get started</p>
          </div>
        )}
      </div>

      {/* Note Dialog */}
      <NoteDialog
        isOpen={noteDialog.isOpen}
        onClose={() => setNoteDialog({ isOpen: false, data: null })}
        onSubmit={noteDialog.data ? handleUpdateNote : handleCreateNote}
        initialData={noteDialog.data}
        personId={personId}
      />
    </div>
  )
}
