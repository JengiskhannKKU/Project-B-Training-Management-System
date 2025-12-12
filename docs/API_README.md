# API Usage Guide

Frontend Developer Guide for Training Management System API

**Version:** 1.0.0
**Last Updated:** December 12, 2025

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Authentication](#authentication)
3. [Making API Calls](#making-api-calls)
4. [Code Examples](#code-examples)
5. [Error Handling](#error-handling)
6. [Best Practices](#best-practices)

---

## Quick Start

### Base URL

```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### Setup in 3 Steps

**1. Register/Login to get token:**
```javascript
const response = await fetch('http://localhost:8000/api/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com',
    password: 'password123'
  })
});

const data = await response.json();
const token = data.data.token; // Save this
```

**2. Store token:**
```javascript
localStorage.setItem('token', token);
```

**3. Use token in requests:**
```javascript
const response = await fetch('http://localhost:8000/api/programs', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

---

## Authentication

### Token Storage

**Recommended approach:**
```javascript
// Save token after login
localStorage.setItem('token', token);

// Retrieve token for API calls
const token = localStorage.getItem('token');
```

**With state management:**
```javascript
// Redux / Zustand / Pinia
store.setToken(token);
const token = store.getToken();
```

### Token Usage

Always include in headers for protected endpoints:
```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json'
}
```

### Handle Token Expiration

```javascript
if (response.status === 401) {
  // Token expired or invalid
  localStorage.removeItem('token');
  window.location.href = '/login';
}
```

---

## Making API Calls

### Create an API Helper

**api.js:**
```javascript
const API_BASE = 'http://localhost:8000/api';

async function apiCall(endpoint, options = {}) {
  const token = localStorage.getItem('token');

  const config = {
    ...options,
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...options.headers
    }
  };

  const response = await fetch(`${API_BASE}${endpoint}`, config);

  // Handle 401 Unauthorized
  if (response.status === 401) {
    localStorage.removeItem('token');
    window.location.href = '/login';
    return;
  }

  return response.json();
}

export default apiCall;
```

### Usage

```javascript
import apiCall from './api';

// GET request
const programs = await apiCall('/programs');

// POST request
const newProgram = await apiCall('/programs', {
  method: 'POST',
  body: JSON.stringify({
    name: 'Vue.js Course',
    code: 'VUE-001',
    category: 'Frontend',
    duration_hours: 40,
    status: 'active'
  })
});

// PUT request
const updated = await apiCall('/programs/1', {
  method: 'PUT',
  body: JSON.stringify({ name: 'Updated Name' })
});

// DELETE request
const deleted = await apiCall('/programs/1', {
  method: 'DELETE'
});
```

---

## Code Examples

### React - Fetch Programs

```javascript
import { useState, useEffect } from 'react';
import apiCall from './api';

function ProgramList() {
  const [programs, setPrograms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchPrograms() {
      try {
        const result = await apiCall('/programs');
        if (result.success) {
          setPrograms(result.data);
        }
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }
    fetchPrograms();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      {programs.map(program => (
        <div key={program.id}>
          <h3>{program.name}</h3>
          <p>{program.description}</p>
          <p>Duration: {program.duration_hours}h</p>
        </div>
      ))}
    </div>
  );
}
```

### React - Create Program Form

```javascript
import { useState } from 'react';
import apiCall from './api';

function CreateProgramForm() {
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    category: '',
    duration_hours: '',
    status: 'active'
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      const result = await apiCall('/programs', {
        method: 'POST',
        body: JSON.stringify(formData)
      });

      if (result.success) {
        alert('Program created successfully!');
        // Reset form or redirect
        setFormData({ name: '', code: '', category: '', duration_hours: '', status: 'active' });
      }
    } catch (err) {
      if (err.errors) {
        setErrors(err.errors);
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div>
        <input
          type="text"
          name="name"
          placeholder="Program Name"
          value={formData.name}
          onChange={handleChange}
        />
        {errors.name && <span className="error">{errors.name[0]}</span>}
      </div>

      <div>
        <input
          type="text"
          name="code"
          placeholder="Code (e.g., LAR-001)"
          value={formData.code}
          onChange={handleChange}
        />
        {errors.code && <span className="error">{errors.code[0]}</span>}
      </div>

      <div>
        <input
          type="text"
          name="category"
          placeholder="Category"
          value={formData.category}
          onChange={handleChange}
        />
        {errors.category && <span className="error">{errors.category[0]}</span>}
      </div>

      <div>
        <input
          type="number"
          name="duration_hours"
          placeholder="Duration (hours)"
          value={formData.duration_hours}
          onChange={handleChange}
        />
        {errors.duration_hours && <span className="error">{errors.duration_hours[0]}</span>}
      </div>

      <button type="submit" disabled={submitting}>
        {submitting ? 'Creating...' : 'Create Program'}
      </button>
    </form>
  );
}
```

### Vue 3 - Fetch Sessions by Program

```vue
<template>
  <div>
    <select v-model="selectedProgramId" @change="fetchSessions">
      <option value="">All Programs</option>
      <option v-for="program in programs" :key="program.id" :value="program.id">
        {{ program.name }}
      </option>
    </select>

    <div v-if="loading">Loading...</div>
    <div v-else>
      <div v-for="session in sessions" :key="session.id" class="session-card">
        <h4>{{ session.title }}</h4>
        <p>{{ session.start_date }} - {{ session.end_date }}</p>
        <p>Capacity: {{ session.capacity }}</p>
        <p>Location: {{ session.location }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiCall from './api';

const programs = ref([]);
const sessions = ref([]);
const selectedProgramId = ref('');
const loading = ref(false);

async function fetchPrograms() {
  const result = await apiCall('/programs');
  if (result.success) {
    programs.value = result.data;
  }
}

async function fetchSessions() {
  loading.value = true;
  const endpoint = selectedProgramId.value
    ? `/sessions?program_id=${selectedProgramId.value}`
    : '/sessions';

  const result = await apiCall(endpoint);
  if (result.success) {
    sessions.value = result.data;
  }
  loading.value = false;
}

onMounted(() => {
  fetchPrograms();
  fetchSessions();
});
</script>
```

### React Query - Advanced Usage

```javascript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiCall from './api';

function ProgramManager() {
  const queryClient = useQueryClient();

  // Fetch programs
  const { data, isLoading, error } = useQuery({
    queryKey: ['programs'],
    queryFn: () => apiCall('/programs')
  });

  // Create program mutation
  const createMutation = useMutation({
    mutationFn: (newProgram) => apiCall('/programs', {
      method: 'POST',
      body: JSON.stringify(newProgram)
    }),
    onSuccess: () => {
      // Invalidate and refetch
      queryClient.invalidateQueries(['programs']);
    }
  });

  // Update program mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => apiCall(`/programs/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    }),
    onSuccess: () => {
      queryClient.invalidateQueries(['programs']);
    }
  });

  // Delete program mutation
  const deleteMutation = useMutation({
    mutationFn: (id) => apiCall(`/programs/${id}`, {
      method: 'DELETE'
    }),
    onSuccess: () => {
      queryClient.invalidateQueries(['programs']);
    }
  });

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return (
    <div>
      {data?.data?.map(program => (
        <div key={program.id}>
          <h3>{program.name}</h3>
          <button onClick={() => updateMutation.mutate({
            id: program.id,
            data: { status: 'inactive' }
          })}>
            Deactivate
          </button>
          <button onClick={() => deleteMutation.mutate(program.id)}>
            Delete
          </button>
        </div>
      ))}
    </div>
  );
}
```

---

## Error Handling

### Comprehensive Error Handler

```javascript
async function apiCallWithErrorHandling(endpoint, options = {}) {
  try {
    const token = localStorage.getItem('token');

    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
      }
    });

    const data = await response.json();

    // Handle different status codes
    if (!response.ok) {
      switch (response.status) {
        case 401:
          // Unauthorized - redirect to login
          localStorage.removeItem('token');
          window.location.href = '/login';
          throw new Error('Please login again');

        case 403:
          // Forbidden
          throw new Error('You do not have permission to perform this action');

        case 404:
          // Not found
          throw new Error('Resource not found');

        case 422:
          // Validation errors
          return { success: false, errors: data.errors };

        case 500:
          // Server error
          throw new Error('Server error. Please try again later.');

        default:
          throw new Error(data.message || 'An error occurred');
      }
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}
```

### Display Validation Errors

```javascript
function FormWithValidation() {
  const [errors, setErrors] = useState({});

  const handleSubmit = async (formData) => {
    setErrors({});

    const result = await apiCallWithErrorHandling('/programs', {
      method: 'POST',
      body: JSON.stringify(formData)
    });

    if (!result.success && result.errors) {
      setErrors(result.errors);
      return;
    }

    // Success
    alert('Created successfully!');
  };

  return (
    <div>
      <input name="name" />
      {errors.name && errors.name.map((error, i) => (
        <p key={i} className="error">{error}</p>
      ))}
    </div>
  );
}
```

---

## Best Practices

### 1. Create an API Service Class

**api-service.js:**
```javascript
class APIService {
  constructor(baseURL) {
    this.baseURL = baseURL;
  }

  getToken() {
    return localStorage.getItem('token');
  }

  async request(endpoint, options = {}) {
    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${this.getToken()}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
      }
    });

    if (response.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
      return;
    }

    return response.json();
  }

  // Programs
  async getPrograms() {
    return this.request('/programs');
  }

  async getProgram(id) {
    return this.request(`/programs/${id}`);
  }

  async createProgram(data) {
    return this.request('/programs', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async updateProgram(id, data) {
    return this.request(`/programs/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async deleteProgram(id) {
    return this.request(`/programs/${id}`, {
      method: 'DELETE'
    });
  }

  // Sessions
  async getSessions(programId = null) {
    const endpoint = programId
      ? `/sessions?program_id=${programId}`
      : '/sessions';
    return this.request(endpoint);
  }

  async getSession(id) {
    return this.request(`/sessions/${id}`);
  }

  async createSession(data) {
    return this.request('/sessions', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async updateSession(id, data) {
    return this.request(`/sessions/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async deleteSession(id) {
    return this.request(`/sessions/${id}`, {
      method: 'DELETE'
    });
  }
}

export default new APIService('http://localhost:8000/api');
```

**Usage:**
```javascript
import api from './api-service';

// Fetch programs
const result = await api.getPrograms();

// Create program
const newProgram = await api.createProgram({
  name: 'Vue.js Course',
  code: 'VUE-001',
  category: 'Frontend',
  duration_hours: 40,
  status: 'active'
});

// Get sessions for a program
const sessions = await api.getSessions(1);
```

### 2. Handle Loading States

```javascript
function Component() {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState(null);

  async function fetchData() {
    setLoading(true);
    try {
      const result = await api.getPrograms();
      setData(result.data);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      {loading && <Spinner />}
      {!loading && data && <DataList items={data} />}
    </div>
  );
}
```

### 3. Use Environment Variables

**.env:**
```
VITE_API_URL=http://localhost:8000/api
```

**config.js:**
```javascript
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
```

### 4. Implement Request Interceptors

For axios users:
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api'
});

// Request interceptor
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### 5. Debounce Search Requests

```javascript
import { useState, useEffect } from 'react';
import { debounce } from 'lodash';

function SearchPrograms() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);

  useEffect(() => {
    const debouncedSearch = debounce(async (searchQuery) => {
      if (searchQuery.length > 2) {
        const result = await api.getPrograms(); // Add search param
        setResults(result.data);
      }
    }, 300);

    debouncedSearch(query);
  }, [query]);

  return (
    <input
      type="text"
      value={query}
      onChange={(e) => setQuery(e.target.value)}
      placeholder="Search programs..."
    />
  );
}
```

---

## Quick Reference

### Common Operations

```javascript
// Get all programs
const programs = await api.getPrograms();

// Get programs by filter (sessions only)
const sessions = await api.getSessions(programId);

// Create program
const newProgram = await api.createProgram({
  name: 'Program Name',
  code: 'CODE-001',
  category: 'Category',
  duration_hours: 40,
  status: 'active'
});

// Update program
const updated = await api.updateProgram(id, {
  name: 'New Name'
});

// Delete program
await api.deleteProgram(id);
```

### Response Structure

All successful responses:
```javascript
{
  success: true,
  message: "Operation successful",
  data: { ... }
}
```

Validation errors:
```javascript
{
  message: "Validation failed",
  errors: {
    field_name: ["Error message 1", "Error message 2"]
  }
}
```

---

## Additional Resources

- **API Specification:** See `API_SPECIFICATION.md` for complete endpoint documentation
- **Admin Guide:** See `ADMIN_GUIDE.md` for admin operations
- **Testing:** See `../TESTING_SUMMARY.md` for test coverage

---

**Last Updated:** December 12, 2025
**Version:** 1.0.0
