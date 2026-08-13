import axios, { type AxiosError } from 'axios'

interface ErrorResponseBody {
  message?: string
  errors?: Record<string, string[]>
}

export const getApiErrorMessage = (error: unknown, fallback = 'Terjadi kesalahan.'): string => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ErrorResponseBody>

    const validationMessage = axiosError.response?.data?.errors
      ? Object.values(axiosError.response.data.errors).flat().find(Boolean)
      : null

    if (validationMessage) {
      return validationMessage
    }

    if (axiosError.response?.data?.message) {
      return axiosError.response.data.message
    }

    if (axiosError.code === 'ECONNABORTED') {
      return 'Permintaan melebihi batas waktu. Silakan coba lagi.'
    }

    if (!axiosError.response) {
      return 'Koneksi ke server gagal. Periksa jaringan Anda.'
    }
  }

  if (error instanceof Error && error.message) {
    return error.message
  }

  return fallback
}
